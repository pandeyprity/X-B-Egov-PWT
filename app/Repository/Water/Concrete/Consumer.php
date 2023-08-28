<?php

namespace App\Repository\Water\Concrete;

use App\Models\UlbMaster;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterFixedMeterRate;
use App\Models\Water\WaterMeterRate;
use App\Repository\Water\Interfaces\IConsumer;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\WardPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Consumer implements IConsumer
{
    use Auth;               // Trait Used added by sandeep bara date 15-12-2022
    use WardPermission;
    use Razorpay;


    public function calConsumerDemand(Request $request)
    {
        try {
            $mNowDate             = Carbon::now()->format('Y-m-d');
            $rules = [
                'consumerId'       => "required|digits_between:1,9223372036854775807",
                "demandUpto"       => "nullable|date|date_format:Y-m-d|before_or_equal:$mNowDate",
                'finalRading'      => "nullable|numeric",
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }
            $demandUpto = Carbon::now()->format("Y-m-d");
            if ($request->demandUpto) {
                $demandUpto = $request->demandUpto;
            }
            $tax = $this->generate_demand($request, $request->consumerId, $demandUpto, $request->finalRading);
            return $tax;
        } catch (Exception $e) {
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function generate_demand($req, $consumer_id, $upto_date = null, $final_reading = 0)
    {
        try {
            $refLastDemandDetails = $this->getConsumerLastDemad($consumer_id);
            $refConsumerDetails   = $this->getConsumrerDtlById($consumer_id);
            $refMeterStatus       = $this->getConsumerMeterStatus($consumer_id);

            $mNowDate             = Carbon::now()->format('Y-m-d');
            $mLastDemandUpto      = $refLastDemandDetails->demand_upto ?? "";
            $mAreaSqmt            = $refConsumerDetails->area_sqmt;
            $mPropertyTypeId      = $refConsumerDetails->property_type_id;
            $mCategory            = !empty(trim($refConsumerDetails->category)) ? trim($refConsumerDetails->category) : "APL";
            if (!$refConsumerDetails) {
                throw new Exception("Consumer not Found..");
            }
            if ((!empty($refLastDemandDetails) && $mLastDemandUpto == "") || $mPropertyTypeId <= 0 || $refConsumerDetails->area_sqmt <= 0) {
                throw new Exception("Update your area or property type!!!");
            }
            if (empty($refMeterStatus) || $refMeterStatus->connection_date == '') {
                throw new Exception("Connection Date Not Found!!!");
            }
            if (in_array($refMeterStatus->connection_type, [1, 2]) && $final_reading == 0) {
                throw new Exception("Please Enter Valide Meter Reading!!!");
            }
            if ($mLastDemandUpto == "") {
                $mLastDemandUpto = $refMeterStatus->connection_date;
                $demand_from     = $mLastDemandUpto;
            } else {
                $demand_from    = date('Y-m-d', strtotime($mLastDemandUpto . "+1 days"));
            }

            if ($refMeterStatus->connection_type == "3" || $refMeterStatus->connection_type == "") {
                return $this->fixedDemand($refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date);
            } elseif (in_array($refMeterStatus->connection_type, [1, 2]) && $refMeterStatus->meter_status == "0" && ($mPropertyTypeId == 3 || (!empty($refLastDemandDetails) && $mPropertyTypeId != 3))) {
                return $this->averageBulling($req, $refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date, $final_reading);
            } elseif (in_array($refMeterStatus->connection_type, [1, 2]) && $refMeterStatus->meter_status == "1") {
                return $this->meterDemand($req, $refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date, $final_reading);
            } else {
                throw new Exception("No Anny Rules Applied");
            }
        } catch (Exception $e) {
            // dd($e->getMessage(),$e->getLine());
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function fixedDemand($refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date = null)
    {
        $response["consumer_tax"]    = (array)null;
        try {
            $conter = 0;
            $mNowDate             = Carbon::now()->format('Y-m-d');
            $mLastDemandUpto      = $refLastDemandDetails->demand_upto ?? "";
            $mAreaSqmt            = $refConsumerDetails->area_sqmt;
            $mPropertyTypeId      = $refConsumerDetails->property_type_id;
            $mCategory            = !empty(trim($refConsumerDetails->category)) ? trim($refConsumerDetails->category) : "APL";
            $to_date = "";
            if ($upto_date == "") {
                $to_date = date("Y-m-t", strtotime("-1 months"));
            } else {
                $to_date = date('Y-m-t', strtotime($upto_date . '-1 months'));
            }
            if ($demand_from >= $to_date) {
                throw new Exception("Demand Already Genrated");
            }
            if ($refMeterStatus->connection_type == 3 || $refMeterStatus->connection_type == "") {

                $refFixedRateDetail     = $this->getFixedRateCharge($mPropertyTypeId, $mAreaSqmt, $demand_from);
                $rate_effect_details = $this->getFixedRateEffectBetweenDemandGeneration($mPropertyTypeId, $mAreaSqmt, $demand_from);

                if (empty($refFixedRateDetail)) {
                    $demand_from = $rate_effect_details[0]['effective_date'];
                    $refFixedRateDetail =  $rate_effect_details[0];
                }
                $fixed_amount = $refFixedRateDetail->amount;
                if (sizeOf($rate_effect_details) > 0) {
                    $i = $demand_from;
                    if ($upto_date == "") {
                        $to_date = date("Y-m-t", strtotime("-1 months"));
                    } else {
                        $to_date = date('Y-m-t', strtotime($upto_date . '-1 months'));
                    }

                    $upto_date = $to_date;
                    $j = 1;
                    $flag = 0;
                    $rate_array = [];
                    $last_rate_array = "";
                    foreach ($rate_effect_details as $val) {
                        $rate_array[] = ["id" => $val['id'], "effective_date" => $val['effective_date'], "amount" => $val['amount']];
                        $last_rate_array = ["id" => $val['id'], "effective_date" => $val['effective_date'], "amount" => $val['amount']];
                    }

                    $rate_array[] = ["id" => $last_rate_array['id'], "effective_date" => date('Y-m-t', strtotime(date('Y-m-d') . "-1 month ")), "amount" => $last_rate_array['amount']];
                    $z = 0;
                    foreach ($rate_array as $val) {
                        $z++;
                        if ($i > $val['effective_date'] and $flag == 1) {
                            $i = $val['effective_date'];
                        }

                        $consumer_tax = array();
                        $consumer_tax['charge_type'] = 'Fixed';
                        $consumer_tax['rate_id'] = $val['id'];
                        $consumer_tax['amount'] = $fixed_amount;
                        $consumer_tax['effective_from'] = $i;
                        $consumer_tax['initial_reading'] = 0;
                        $consumer_tax['final_reading'] = 0;
                        // $consumer_tax_id = $this->consumer_tax_model->insertData($consumer_tax);
                        $Tdemands = (array)null;
                        $Tconter = $conter;
                        if ($i < $val['effective_date']) {
                            $response["consumer_tax"][$conter] = $consumer_tax;
                            $conter++;
                        }
                        while ($i < $val['effective_date']) {
                            $flag = 1;
                            if ($i < $upto_date) {
                                $last_date_of_current_month = date('Y-m-t', strtotime($i));
                                if ($last_date_of_current_month > $to_date) {
                                    $last_date_of_current_month = $to_date;
                                    $demand_upto = date('Y-m-d', strtotime($to_date . "-1 days"));
                                } else {
                                    $demand_upto = date('Y-m-t', strtotime($i));
                                }

                                $date_diff_upto = date('Y-m-d', strtotime($last_date_of_current_month . "+1 days"));
                                $get_date_diff = $this->getDateDiff($i, $date_diff_upto);
                                $noof_monthday = date('t', strtotime($i));
                                if ($get_date_diff['month_diff'] == 1) {
                                    $total_fixed_amount = $fixed_amount;
                                } elseif ($get_date_diff['day_diff'] > 0) {
                                    $days_diff = $get_date_diff['day_diff'];
                                    $total_fixed_amount = round(($fixed_amount / $noof_monthday) * $days_diff);
                                }

                                $consumer_demand = array();
                                $consumer_demand['generation_date'] = date('Y-m-d');
                                $consumer_demand['amount']      = $total_fixed_amount;
                                $consumer_demand['unit_amount'] = $fixed_amount;
                                $consumer_demand['demand_from'] = $i;
                                $consumer_demand['demand_upto'] = $demand_upto;
                                $consumer_demand['penalty']     = 0;
                                $consumer_demand['connection_type'] = 'Fixed';
                                $Tdemands[] = $consumer_demand;
                                $i = date('Y-m-d', strtotime($demand_upto . "+1 days"));
                            }
                            $response["consumer_tax"][$Tconter]["consumer_demand"] = $Tdemands;
                        }
                        $fixed_amount = $val['amount'];
                        $j++;
                    }
                } else {
                    $i = $demand_from;
                    if ($upto_date == "") {
                        $to_date = date("Y-m-t", strtotime("-1 months"));
                    } else {
                        $to_date = date('Y-m-t', strtotime($upto_date . '-1 months'));
                    }
                    $consumer_tax = array();
                    $consumer_tax['charge_type'] = 'Fixed';
                    $consumer_tax['rate_id'] = $refFixedRateDetail->id;
                    $consumer_tax['amount'] = $fixed_amount;
                    $consumer_tax['effective_from'] = $i;
                    $consumer_tax['initial_reading'] = 0;
                    $consumer_tax['final_reading'] = 0;
                    $response["consumer_tax"][$conter] = $consumer_tax;
                    while ($i < $to_date) {
                        $last_date_of_current_month = date('Y-m-t', strtotime($i));
                        if ($last_date_of_current_month > $to_date) {
                            $last_date_of_current_month = $to_date;
                            $demand_upto = date('Y-m-d', strtotime($to_date . "-1 days"));
                        } else {
                            $demand_upto = date('Y-m-t', strtotime($i));
                        }
                        $date_diff_upto = date('Y-m-d', strtotime($last_date_of_current_month . "+1 days"));
                        $get_date_diff = $this->getDateDiff($i, $date_diff_upto);
                        $noof_monthday = date('t', strtotime($i));
                        if ($get_date_diff['month_diff'] == 1) {
                            $total_fixed_amount = $fixed_amount;
                        } elseif ($get_date_diff['day_diff'] > 0) {
                            $days_diff = $get_date_diff['day_diff'];
                            $total_fixed_amount = round(($fixed_amount / $noof_monthday) * $days_diff);
                        }

                        $consumer_demand = array();
                        $consumer_demand['generation_date'] = date('Y-m-d');
                        $consumer_demand['unit_amount'] = $fixed_amount;
                        $consumer_demand['amount'] = $total_fixed_amount;
                        $consumer_demand['demand_from'] = $i;
                        $consumer_demand['demand_upto'] = $demand_upto;
                        $consumer_demand['connection_type'] = 'Fixed';
                        $response["consumer_tax"][$conter]["consumer_demand"][] = $consumer_demand;
                        // $this->consumer_demand_model->insertData($consumer_demand);
                        $i = date('Y-m-d', strtotime($demand_upto . "+1 days"));
                    }
                    $conter++;
                }
            } else {
                throw new Exception("Invalid Rule Sete Called");
            }
            $response["status"] = true;
            return collect($response);
        } catch (Exception $e) {
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function meterDemand($req, $refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date = null, $final_reading = 0)
    {
        $response["consumer_tax"]    = (array)null;
        try {
            $conter = 0;
            $refUser              = authUser($req);
            $refUserId            = $refUser->id ?? 0;
            $refUlbId             = $refUser->ulb_id ?? 0;
            $refUlb             = UlbMaster::select("ulb_type")->find($refUlbId);
            $mDemandId            = false;
            $mPenalty             = 0;
            $mNowDate             = Carbon::now()->format('Y-m-d');
            $mLastDemandUpto      = $refLastDemandDetails->demand_upto ?? "";
            $mAreaSqmt            = $refConsumerDetails->area_sqmt;
            $mPropertyTypeId      = $refConsumerDetails->property_type_id;
            $mCategory            = !empty(trim($refConsumerDetails->category)) ? trim($refConsumerDetails->category) : "APL";
            $to_date = "";
            $mConsumerId         = $refConsumerDetails->id;

            $get_initial_reading = $this->getLastMeterReading($mConsumerId);
            $initial_reading = $get_initial_reading->initial_reading ?? 0;
            if ($final_reading <= $initial_reading) {
                throw new Exception("Final Reading Should be Greatr than Previuse Reading");
            }
            if ($refMeterStatus->connection_type == 1 || $refMeterStatus->connection_type == 2) {
                if ($upto_date == "") {
                    $to_date = date('Y-m-d');
                } else {
                    $to_date = $upto_date;
                }
                $diff_reading = $final_reading - $initial_reading;

                if ($mPropertyTypeId == 1) {
                    $where = " category='$mCategory' and ceil($diff_reading)>=from_unit and ceil($diff_reading)<=upto_unit ";
                } else {
                    $where = " ceil($diff_reading)>=from_unit and ceil($diff_reading)<=upto_unit ";
                }
                $get_meter_calc["meter_rate"] = 9;
                if (isset($refUlb->ulb_type) && $refUlb->ulb_type == 2) {
                    $get_meter_calc["meter_rate"] = 7;
                } elseif (isset($refUlb->ulb_type) && $refUlb->ulb_type == 3) {
                    $get_meter_calc["meter_rate"] = 5;
                }
                $temp_pro = $mPropertyTypeId;
                if (in_array($mPropertyTypeId, [7]))
                    $temp_pro = 1;
                elseif (in_array($mPropertyTypeId, [8]))
                    $temp_pro = 4;
                elseif (!in_array($mPropertyTypeId, [1, 2, 3, 4, 5, 6, 7]))
                    $temp_pro = 8;

                $get_meter_rate_new = $this->getMeterRate($temp_pro, $where);
                // $get_meter_rate_new = $this->revised_meter_rate_model->getMeterRate_new($temp_pro, $where);            
                $temp_diff = $diff_reading;
                $incriment = 0;
                $amount = 0;
                $ret_ids = '';
                $meter_rate_id = 0;
                $meter_calc_rate = 0;
                foreach ($get_meter_rate_new as $key => $val) {
                    $meter_calc_rate = $val['amount'];
                    $meter_calc_factor = $get_meter_calc['meter_rate'];
                    $meter_rate_id = $val['id'];
                    if ($key == 0)
                        $ret_ids .=  $val['id'];
                    else
                        $ret_ids .=  "," . $val['id'];

                    $reading = $incriment + $val['reading'];
                    if ($reading <= $diff_reading && !empty($val['reading'])) {
                        $amount += $meter_calc_rate * $meter_calc_factor * $val['reading'];
                        $reading = $val['reading'];
                    } elseif (empty($val['reading'])) {
                        $reading = $temp_diff - $reading;
                        $amount += $meter_calc_rate * $meter_calc_factor * $reading;
                        break;
                    } else {
                        $reading = $temp_diff - $incriment;
                        $amount += $meter_calc_rate * $meter_calc_factor * $reading;
                        break;
                    }

                    $incriment += $val['reading'];
                }
                $ret_ids            = ltrim($ret_ids, ',');
                $meter_calc_factor  = $get_meter_calc['meter_rate'];
                $meter_rate         = $meter_calc_factor *  $meter_calc_rate;
                $meter_rate_id      = $meter_rate_id;
                $total_amount       = $amount;
                if ($total_amount >= 0) {
                    $consumer_tax = array();
                    $consumer_tax['charge_type'] = 'Meter';
                    $consumer_tax['rate_id'] = $meter_rate_id;
                    $consumer_tax['effective_from'] = $demand_from;
                    $consumer_tax['initial_reading'] = $initial_reading;
                    $consumer_tax['final_reading'] = $final_reading;
                    $consumer_tax['amount'] = $total_amount;

                    $response["consumer_tax"][$conter] = $consumer_tax;

                    $consumer_demand = array();
                    $consumer_demand['generation_date']         = date('Y-m-d');
                    $consumer_demand['amount']                  = $total_amount;
                    $consumer_demand['current_meter_reading']   = $final_reading;
                    $consumer_demand['unit_amount']             = $meter_rate;
                    $consumer_demand['demand_from']             = $demand_from;
                    $consumer_demand['demand_upto']             = $to_date;
                    $consumer_demand['connection_type']         = 'Meter';
                    $response["consumer_tax"][$conter]["consumer_demand"] = $consumer_demand;
                    // $demand_id = $this->consumer_demand_model->insertData($consumer_demand);
                }
            } else {
                throw new Exception("Invalid Rule Sete Called");
            }
            $response["status"] = true;
            return  collect($response);
        } catch (Exception $e) {
            // dd($e->getMessage(),$e->getLine());
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function averageBulling($req, $refConsumerDetails, $refLastDemandDetails, $refMeterStatus, $demand_from, $upto_date = null, $final_reading = 0)
    {
        $response["consumer_tax"]    = (array)null;
        // $response["consumer_tax"]["consumer_demand"] = (array)null;
        try {
            $conter = 0;
            $last_demand_upto = $refLastDemandDetails->demand_upto;
            $prev_connection_details = $refMeterStatus;
            $area_sqmt = $refConsumerDetails->area_sqmt;
            $property_type_id = $refConsumerDetails->property_type_id;
            $category = !empty(trim($refConsumerDetails->category)) ? trim($refConsumerDetails->category) : "APL";
            $mConsumerId = $refConsumerDetails->id;
            $generation_date = date('Y-m-d');

            if ((!empty($refLastDemandDetails) && $last_demand_upto == "") || $property_type_id <= 0 || $refConsumerDetails->area_sqmt <= 0) {
                throw new Exception("Update your area or property type!!!");
            }
            if ($refMeterStatus->connection_type == 3) {
                throw new Exception("Can not Generate average Billig Of this Consumer!!!");
            }
            if (in_array($refMeterStatus->connection_type, [1]) && $property_type_id == 3 && ($refMeterStatus->rate_per_month == 0 || empty($refMeterStatus->rate_per_month))) {
                throw new Exception("Average bulling Rate Not Available Of this Consumer!!!");
            }
            if (empty($refMeterStatus) || $refMeterStatus->connection_date == '') {
                throw new Exception("Connection Date Not Found!!!");
            }
            if ($property_type_id != 3 && in_array($refMeterStatus->connection_type, [1, 2]) && empty($refLastDemandDetails)) {
                throw new Exception("No Meter Demand Found!!!");
            }
            if ($last_demand_upto == "") {
                $last_demand_upto = $refMeterStatus->connection_date;
                $demand_from = $last_demand_upto;
            } else {
                $demand_from = date('Y-m-d', strtotime($last_demand_upto . "+1 days"));
            }

            #for gov property meter Fixed
            if ($property_type_id == 3 && $refMeterStatus->connection_type == 1 && $refMeterStatus->meter_status == 0) {
                $i = $demand_from;
                if ($upto_date == "") {
                    $to_date = date("Y-m-t", strtotime("-1 months"));
                } else {
                    $to_date = date('Y-m-t', strtotime($upto_date . '-1 months'));
                }

                // if ($demand_from >= $to_date) {
                //     throw new Exception("Demand Already Genrated");
                // }

                $consumer_tax = array();
                $consumer_tax['charge_type'] = 'Average';
                $consumer_tax['rate_id'] = null;
                $consumer_tax['amount'] = $prev_connection_details['rate_per_month'] ?? 0;
                $consumer_tax['effective_from'] = $i;
                $response["consumer_tax"][$conter] = $consumer_tax;
                while ($i < $to_date) {
                    $last_date_of_current_month = date('Y-m-t', strtotime($i));
                    if ($last_date_of_current_month > $to_date) {
                        $last_date_of_current_month = $to_date;
                        $demand_upto = date('Y-m-d', strtotime($to_date . "-1 days"));
                    } else {
                        $demand_upto = date('Y-m-t', strtotime($i));
                    }

                    $date_diff_upto = date('Y-m-d', strtotime($last_date_of_current_month . "+1 days"));
                    $get_date_diff = $this->consumer_demand_model->date_diff_water($i, $date_diff_upto);
                    $noof_monthday = date('t', strtotime($i));

                    $fixed_amount = $refMeterStatus->rate_per_month ?? 0;
                    if ($get_date_diff['month_diff'] == 1) {
                        $total_fixed_amount = $fixed_amount;
                    } elseif ($get_date_diff['day_diff'] > 0) {
                        $days_diff = $get_date_diff['day_diff'];
                        $total_fixed_amount = round(($fixed_amount / $noof_monthday) * $days_diff);
                    }

                    $consumer_demand = array();
                    $consumer_demand['generation_date'] = date('Y-m-d');
                    $consumer_demand['unit_amount']     = $refMeterStatus->rate_per_month ?? 0;
                    $consumer_demand['amount']          = $total_fixed_amount;
                    $consumer_demand['demand_from']     = $i;
                    $consumer_demand['demand_upto']     = $demand_upto;
                    $consumer_demand['connection_type'] = 'Meter';

                    $response["consumer_tax"][$conter]["consumer_demand"] = $consumer_demand;
                    // $this->consumer_demand_model->insertData($consumer_demand);
                    $i = date('Y-m-d', strtotime($demand_upto . "+1 days"));
                }
                $conter++;
            } elseif ($property_type_id != 3 && $refMeterStatus->connection_type == 1 ||  $refMeterStatus->connection_type == 2 && $refMeterStatus->meter_status == 0) {
                // print_r($response);
                $refUser              = authUser($req);
                $refUserId            = $refUser->id ?? 0;
                $refUlbId             = $refUser->ulb_id ?? 0;
                $refUlb             = UlbMaster::select("ulb_type")->find($refUlbId);

                // $get_meter_calc = $this->meter_rate_calc_model->getMeterCalculationRate($this->ulb_type_id);

                $get_meter_calc["meter_rate"] = 9;
                if ($refUlb->ulb_type == 2) {
                    $get_meter_calc["meter_rate"] = 7;
                } elseif ($refUlb->ulb_type == 3) {
                    $get_meter_calc["meter_rate"] = 5;
                }
                if ($upto_date == "") {
                    $to_date = date('Y-m-d');
                } else {
                    $to_date = $upto_date;
                }

                // if ($demand_from >= $to_date) {
                //     throw new Exception("Demand Already Genrated");
                // }

                $args = $this->getMeterArrvg($refConsumerDetails, $refLastDemandDetails, $to_date);

                $get_initial_reading = $this->getLastMeterReading($mConsumerId);
                $initial_reading = $get_initial_reading->initial_reading ?? 0;
                if ($final_reading <= $initial_reading) {
                    throw new Exception("Final Reading Should be Grater than Priviuse Reading!!!");
                }
                $diff_reading = $final_reading - $initial_reading;
                if (!$args) {
                    throw new Exception("Demand Not Generated!!!");
                } elseif (round($args['current_reading']) != round($diff_reading)) {
                    throw new Exception("Average Reading Not Currect !!!");
                }

                if ($property_type_id == 1) {
                    $where = " category='$category' and CEIL($diff_reading)>=from_unit and CEIL($diff_reading)<=upto_unit ";
                } else {
                    $where = " CEIL($diff_reading)>=from_unit and CEIL($diff_reading)<=upto_unit ";
                }

                $temp_pro = $property_type_id;
                if (in_array($property_type_id, [7])) {
                    $temp_pro = 1;
                } elseif (in_array($property_type_id, [8])) {
                    $temp_pro = 4;
                } elseif (!in_array($property_type_id, [1, 2, 3, 4, 5, 6, 7])) {
                    $temp_pro = 8;
                }
                $get_meter_rate_new = $this->getMeterRate($temp_pro, $where);
                //end her
                $temp_diff = $diff_reading;
                $incriment = 0;
                $amount = 0;
                $ret_ids = '';
                $meter_rate_id = 0;
                $meter_calc_rate = 0;
                foreach ($get_meter_rate_new as $key => $val) {
                    $meter_calc_rate = $val['amount'];
                    $meter_calc_factor = $get_meter_calc['meter_rate'];
                    $meter_rate_id = $val['id'];
                    if ($key == 0)
                        $ret_ids .=  $val['id'];
                    else
                        $ret_ids .=  "," . $val['id'];

                    $reading = $incriment + $val['reading'];
                    if ($reading <= $diff_reading && !empty($val['reading'])) {
                        $amount += $meter_calc_rate * $meter_calc_factor * $val['reading'];
                        $reading = $val['reading'];
                    } elseif (empty($val['reading'])) {
                        $reading = $temp_diff - $reading;
                        $amount += $meter_calc_rate * $meter_calc_factor * $reading;
                        break;
                    } else {
                        $reading = $temp_diff - $incriment;
                        $amount += $meter_calc_rate * $meter_calc_factor * $reading;
                        break;
                    }

                    $incriment += $val['reading'];
                }
                $ret_ids = ltrim($ret_ids, ',');
                $meter_calc_factor = $get_meter_calc['meter_rate'];
                $meter_rate = $meter_calc_factor *  $meter_calc_rate;
                $meter_rate_id = $meter_rate_id;
                $total_amount = $amount;
                if ($total_amount >= 0) {
                    $consumer_tax = array();
                    $consumer_tax['charge_type']    = 'Average';
                    $consumer_tax['rate_id']        = $meter_rate_id;
                    $consumer_tax['initial_reading'] = $initial_reading;
                    $consumer_tax['final_reading']  = $final_reading;
                    $consumer_tax['amount']         = $total_amount;
                    $consumer_tax['effective_from'] = date('Y-m-d');

                    // $consumer_tax_id = $this->consumer_tax_model->insertData($consumer_tax);
                    // $response["consumer_tax"][$conter] = $consumer_tax;

                    $response["consumer_tax"][$conter] = $consumer_tax;

                    $consumer_demand = array();
                    $consumer_demand['generation_date'] = date('Y-m-d');
                    $consumer_demand['amount'] = $total_amount;
                    $consumer_demand['current_meter_reading '] = $final_reading;
                    $consumer_demand['unit_amount'] = $args['arvg'];
                    $consumer_demand['demand_from'] = $demand_from;
                    $consumer_demand['demand_upto'] = $to_date;
                    $consumer_demand['connection_type'] = 'Meter';

                    $response["consumer_tax"][$conter]["consumer_demand"] = $consumer_demand;
                    $conter++;
                    // $demand_id = $this->consumer_demand_model->insertData($consumer_demand);
                }
            } else {
                throw new Exception("Invalid Rule Sete Called");
            }
            $response["status"] = true;
            return collect($response);
        } catch (Exception $e) {
            // dd($e->getMessage(),$e->getLine());
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function getMeterArrvg($refConsumerDetails, $refLastDemandDetails, $upto_date = null)
    {
        try {
            $consumer_id = $refConsumerDetails->id;
            $get_initial_reading   = $this->getLastMeterReading($consumer_id);
            $secondLastReading     = $this->get2ndLastMeterReading($consumer_id, $get_initial_reading['id'] ?? 0);
            $lastDemand = $refLastDemandDetails;

            if ($upto_date == "") {
                $to_date = date('Y-m-d');
            } else {
                $to_date = $upto_date;
            }
            $date1 = date_create($lastDemand['demand_upto']);
            $date2 = date_create($lastDemand['demand_from']);
            $date3 = date_create($to_date);
            $diff = date_diff($date2, $date1);
            $no_diff = $diff->format("%a");
            $current_diff = date_diff($date3, $date1)->format("%a");
            $reading = ($get_initial_reading['initial_reading'] ?? 0) - ($secondLastReading['initial_reading'] ?? 0);
            $arvg = round(($no_diff != 0 ? ($reading / $no_diff) : 1), 2);
            $current_reading = ($current_diff * $arvg);

            return [
                "priv_demand_from" => $lastDemand['demand_from'],
                "priv_demand_upto" => $lastDemand['demand_upto'],
                "demand_from" => $lastDemand['demand_upto'],
                "demand_upto" => $to_date,
                "priv_day_diff" => $no_diff,
                "current_day_diff" => $current_diff,
                "last_reading" => $reading,
                "current_reading" => round($current_reading, 2),
                "arvg" => $arvg,
            ];
        } catch (Exception $e) {
            return [];
        }
    }


    #------------------- core function ---------------------------------
    public function getFixedRateCharge($property_type_id, $area_sqmt, $demand_from)
    {
        try {
            // DB::enableQueryLog();
            $rate = WaterFixedMeterRate::select("*")
                ->where("property_type_id", $property_type_id)
                ->where("range_from", "<=", ceil($area_sqmt))
                ->where("range_upto", ">=", ceil($area_sqmt))
                ->where("effective_date", "<", $demand_from)
                ->where("type", "Fixed")
                ->orderBy("effective_date", "DESC")
                ->first();
            // dd(DB::getQueryLog());
            return $rate;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getFixedRateEffectBetweenDemandGeneration($property_type_id, $area_sqmt, $demand_from)
    {
        try {
            // DB::enableQueryLog();
            $rate = WaterFixedMeterRate::select("*")
                ->where("property_type_id", $property_type_id)
                ->where("range_from", "<=", ceil($area_sqmt))
                ->where("range_upto", ">=", ceil($area_sqmt))
                ->where("effective_date", ">=", $demand_from)
                ->where("type", "Fixed")
                ->orderBy("effective_date", "ASC")
                ->get();
            // dd(DB::getQueryLog());
            return $rate;
            $sql = "select * 
                from tbl_fixed_meter_rate 
                where property_type_id=$property_type_id 
                    and ceil($area_sqmt) >= range_from and ceil($area_sqmt) <= range_upto 
                    and effective_date>='$demand_from' and type='Fixed' 
                    order by effective_date asc";
            $run = $this->db->query($sql);
            $result = $run->getResultArray();
            return $result;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getConsumrerDtlById($id)
    {
        try {
            $consumer = WaterConsumer::select("*")
                ->where("id", $id)
                ->where("status", 1)
                ->first();
            return $consumer;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getConsumerMeterStatus($consumer_id)
    {
        try {
            $meterSatatus = WaterConsumerMeter::select("*")
                ->where("status", 1)
                ->where("consumer_id", $consumer_id)
                ->orderBy("connection_date", "DESC")
                ->orderBy("id", "DESC")
                ->first();
            return $meterSatatus;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getLastMeterReading($consumer_id)
    {
        try {
            $reading = WaterConsumerInitialMeter::select("*")
                ->where("consumer_id", $consumer_id)
                ->where("status", 1)
                ->orderBy("id", "DESC")
                ->orderBy("created_at", "DESC")
                ->first();
            return $reading;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function get2ndLastMeterReading($consumer_id, $last_id)
    {
        try {
            $reading = WaterConsumerInitialMeter::select("*")
                ->where("consumer_id", $consumer_id)
                ->where("status", 1)
                ->where("id", "<", $last_id)
                ->orderBy("id", "DESC")
                ->orderBy("created_at", "DESC")
                ->first();
            return $reading;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getConsumerLastDemad($consumer_id)
    {
        try {
            $demand = WaterConsumerDemand::select("*")
                ->where("status", 1)
                ->where("consumer_id", $consumer_id)
                ->orderBy("demand_upto", "DESC")
                ->first();
            return $demand;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getMeterRate($property_type_id, $where)
    {
        // DB::enableQueryLog();
        $rate = WaterMeterRate::select("*")
            ->where("property_type_id", $property_type_id)
            ->whereRaw($where)
            ->where("status", 1)
            ->orderBy("effective_date")
            ->get();
        // dd(DB::getQueryLog());
        return $rate;
    }

    public function getDateDiff($from_date, $to_date)
    {
        $datetime1 = date_create($from_date);
        $datetime2 = date_create($to_date);
        // Calculates the difference between DateTime objects
        $interval = date_diff($datetime1, $datetime2);
        $inter = $interval->format('%y--%m--%d');
        $date = explode("--", $inter);
        $data["year_diff"] = $date[0];
        $data["month_diff"] = $date[1];
        $data["day_diff"] = $date[2];
        return ($data);
    }


    /**
     * | get consumer details , owners details , and site inspection details 
     */
    public function getconsumerRelatedData($applicationId)
    {
        $refJe = Config::get("waterConstaint.ROLE-LABEL");
        return WaterApprovalApplicationDetail::select(
            'water_approval_application_details.id',
            'water_approval_application_details.application_no',
            'water_approval_application_details.ward_id',
            'water_approval_application_details.address',
            'water_approval_application_details.holding_no',
            'water_approval_application_details.saf_no',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_param_pipeline_types.pipeline_type as pipeline_type_name',
            'site.property_type_id AS site_property_type_id',
            'site.pipeline_type_id AS site_pipeline_type_id',
            DB::raw("string_agg(water_approval_applicants.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_approval_applicants.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_approval_applicants.guardian_name,',') as guardianName"),
        )
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_approval_application_details.ulb_id')
            ->join('water_approval_applicants', 'water_approval_applicants.application_id', '=', 'water_approval_application_details.id')
            ->join(
                DB::raw("(SELECT * FROM water_site_inspections
                                WHERE order_officer = '" . $refJe['JE'] . "'
                                AND apply_connection_id = $applicationId
            	                ORDER BY id DESC 
            	                LIMIT 1
                        )as site "),
                function ($join) {
                    $join->on("site.apply_connection_id", "=", "water_approval_application_details.id");
                }
            )
            ->join("water_param_pipeline_types", "water_param_pipeline_types.id", "site.pipeline_type_id")
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_approval_application_details.ward_id')
            ->where('water_approval_application_details.status', true)
            ->where('water_approval_application_details.id', $applicationId)
            ->groupBy(
                'water_approval_application_details.saf_no',
                'water_approval_application_details.holding_no',
                'water_approval_application_details.address',
                'water_approval_application_details.id',
                'water_approval_applicants.application_id',
                'water_approval_application_details.application_no',
                'water_approval_application_details.ward_id',
                'water_approval_application_details.ulb_id',
                'ulb_ward_masters.ward_name',
                'ulb_masters.id',
                'ulb_masters.ulb_name',
                'water_param_pipeline_types.pipeline_type',
                'site.property_type_id',
                'site.pipeline_type_id',
            )
            ->first();
    }
}
