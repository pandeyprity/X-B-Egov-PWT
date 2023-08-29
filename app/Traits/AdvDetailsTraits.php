<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Created for Gettting Dynamic Saf Details
 */
trait AdvDetailsTraits
{
    /**
     * | Get Basic Details for Self Advertisement
     */
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentWardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entityWardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['m_license_year']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Resident Ward No', 'key' => 'residentWardNo', 'value' => $data['ward_no']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Aadhar No', 'key' => 'aadharNo', 'value' => $data['aadhar_no']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Brand Display Name', 'key' => 'brandDisplayName', 'value' => $data['brand_display_name']],
            ['displayString' => 'Display Name', 'key' => 'mDisplayType', 'value' => $data['m_display_type']],
            ['displayString' => 'Installation Location', 'key' => 'mInstallationLocation', 'value' => $data['m_installation_location']],
            ['displayString' => 'Type', 'key' => 'type', 'value' => $data['type']]
        ]);
    }



    /**
     * | Get Card Details for Self Advertisement
     */
    public function generateCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
        ]);
    }

    /**
     * | Generate Owner Details
     */
    public function generateUploadDocDetails($documentUploads)
    {
        return collect($documentUploads)->map(function ($documentUpload, $key) {
            return new Collection([
                $key + 1,
                $documentUpload['document_name'],
                $documentUpload['verified_by'],
                $documentUpload['verified_on'],
                $documentUpload['document_path']
            ]);
        });
    }


    /**
     * | Generate License Details
     */
    public function generateLicenseDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicantNo', 'value' => $data->application_no],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => Carbon::createFromFormat('Y-m-d',  $data->application_date)->format('d-m-Y')],
            ['displayString' => 'Licence No', 'key' => 'licenseNo', 'value' => $data->license_no],
            ['displayString' => 'Valid From', 'key' => 'validFrom', 'value' => $data->valid_from],
            ['displayString' => 'Valid Upto', 'key' => 'validUpto', 'value' => $data->valid_upto],
            ['displayString' => 'Years', 'key' => 'licenceForYears', 'value' => $data->licence_for_years],
            ['displayString' => 'Firm Name', 'key' => 'firmName', 'value' => $data->firm_name],
            ['displayString' => 'Owner Name', 'key' => 'premisesOwnerName', 'value' => $data->premises_owner_name],
            ['displayString' => 'Address', 'key' => 'address', 'value' => $data->address],
            ['displayString' => 'Landmark', 'key' => 'landmark', 'value' => $data->landmark],
            ['displayString' => 'Pin Code', 'key' => 'pinCOde', 'value' => $data->pin_code],
        ]);
    }


    /**
     * |-----------------------------------------------
     * |================ Bikash Kumar =================
     * |================ 19-01-2023 ===================
     * |================ Movable Vehicles =============
     * |-----------------------------------------------
     * */


    /**
     * | Get Vehicle Basic Details
     */
    public function generateVehicleBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Applicantion No', 'key' => 'applicantionNo', 'value' => $data['application_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentWardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $data['ward_no']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Permanent Address', 'key' => 'permanentAddress', 'value' => $data['permanent_address']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            // ['displayString' => 'Ward ID', 'key' => 'wardId', 'value' => $data['ward_id']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Aadhar No', 'key' => 'aadharNo', 'value' => $data['aadhar_no']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Display Name', 'key' => 'mDisplayType', 'value' => $data['m_display_type']],
            ['displayString' => 'Vehicle No', 'key' => 'vehicleNo', 'value' => $data['vehicle_no']],
            ['displayString' => 'Vehicle Name', 'key' => 'vehicleName', 'value' => $data['vehicle_name']],
            ['displayString' => 'Vehicle Type', 'key' => 'vehicleType', 'value' => $data['vehicleType']],
            ['displayString' => 'Front Area', 'key' => 'frontArea', 'value' => $data['front_area']],
            ['displayString' => 'Rear Area', 'key' => 'rearArea', 'value' => $data['rear_area']],
            ['displayString' => 'Side Area', 'key' => 'sideArea', 'value' => $data['side_area']],
            ['displayString' => 'Top Area', 'key' => 'topArea', 'value' => $data['top_area']],
            ['displayString' => 'Typology', 'key' => 'typology', 'value' => $data['typology']],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' =>  $data['zone']== '1'? 'A' :($data['zone']== '2'?'B' :($data['zone']== '3'?'C':'N/A'))],
        ]);
    }


    /**
     * | Get Vehicle Card Details
     */
    public function generateVehicleCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' =>  $data['zone']== '1'? 'A' :($data['zone']== '2'?'B' :($data['zone']== '3'?'C':'N/A'))],
        ]);
    }



    /**
     * |-----------------------------------------------
     * |================ Bikash Kumar =================
     * |================ 21-01-2023 ===================
     * |================ Agency =======================
     * |-----------------------------------------------
     * */



    public function generateAgencyBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Type', 'key' => 'entityType', 'value' => $data['entityType']],
            ['displayString' => 'Address', 'key' => 'address', 'value' => $data['address']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Fax', 'key' => 'fax', 'value' => $data['fax']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Pan No', 'key' => 'panNo', 'value' => $data['pan_no']],
            ['displayString' => 'Blacklisted', 'key' => 'blacklisted', 'value' => $data['blacklisted'] == 0 ? "No" : "Yes"],
            ['displayString' => 'Pending Amount', 'key' => 'pendingAmount', 'value' => $data['pending_amount']],
            ['displayString' => 'Pending Court Case', 'key' => 'pendingCourtCase', 'value' => $data['pending_court_case'] == 0 ? "No" : "Yes"],
            ['displayString' => 'Application Type', 'key' => 'ApplicationType', 'value' => $data['renewal'] == NULL ? "Fresh" : "Renewal"],
        ]);
    }


    /**
     * | Get Agency Card Details
     */
    public function generateAgencyCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' =>Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
        ]);
    }


    

    /**
     * |-----------------------------------------------
     * |================ Bikash Kumar =================
     * |================ 23-01-2023 ===================
     * |================ Private Land =================
     * |-----------------------------------------------
     * */



    /**
     * | Get Basic Details
     */
    public function generatePrivateLandBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicantionNo', 'value' => $data['application_no']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' =>Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
            ['displayString' => 'Applicant', 'key' => 'applicant', 'value' => $data['applicant']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Aadhar No', 'key' => 'aadharNo', 'value' => $data['aadhar_no']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Licence From', 'key' => 'lLicenseFrom', 'value' => Carbon::createFromFormat('Y-m-d',$data['license_from'])->format('d-m-Y')],
            ['displayString' => 'Licence To', 'key' => 'lLicenseTo', 'value' => Carbon::createFromFormat('Y-m-d',$data['license_to'])->format('d-m-Y')],
            ['displayString' => 'No Of Hording', 'key' => 'noOfHording', 'value' => $data['no_of_hoardings']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Brand Display Name', 'key' => 'brandDisplayName', 'value' => $data['brand_display_name']],
            ['displayString' => 'Resident Ward No', 'key' => 'residentWardNo', 'value' => $data['resident_ward_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentWardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entityWardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'Dispay Type', 'key' => 'dispayType', 'value' => $data['dispayType']],
            ['displayString' => 'Installation Location', 'key' => 'installationLocation', 'value' => $data['installationLocation']],
            ['displayString' => 'Typology', 'key' => 'typology', 'value' => $data['typology']],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' =>  $data['zone']== '1'? 'A' :($data['zone']== '2'?'B' :($data['zone']== '3'?'C':"N/A"))],
        ]);
    }



    /**
     * | Get Card Details
     */
    public function generatePrivateLandCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'Appication No', 'key' => 'appicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Appication Date', 'key' => 'appicationDate', 'value' => Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Permanent Address', 'key' => 'permanentAddress', 'value' => $data['permanent_address']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' =>  $data['zone']== '1'? 'A' :($data['zone']== '2'?'B' :($data['zone']== '3'?'C':'N/A'))],
        ]);
    }


    

    

    /**
     * |-----------------------------------------------
     * |================ Bikash Kumar =================
     * |================ 30-01-2023 ===================
     * |============= Agency Hording License  =========
     * |-----------------------------------------------
     * */



    /**
     * | Get Basic Details
     */
    public function generatehordingDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'License No', 'key' => 'licenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $data['property_type']],
            ['displayString' => 'Property Owner Name', 'key' => 'propertyOwnerName', 'value' => $data['property_owner_name']],
            ['displayString' => 'Property Owner Address', 'key' => 'propertyOwnerAddress', 'value' => $data['property_owner_address']],
            ['displayString' => 'License Year', 'key' => 'licenseYear', 'value' => $data['licenseYear']],
            ['displayString' => 'Property Owner Mobile No', 'key' => 'propertyOwnerMobileNo', 'value' => $data['property_owner_mobile_no']],
            ['displayString' => 'Property Owner Whatsapp No', 'key' => 'propertyOwnerwhatsappNo', 'value' => $data['property_owner_whatsapp_no']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Display Land Mark', 'key' => 'displayLandMark', 'value' => $data['display_land_mark']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Width', 'key' => 'width', 'value' => $data['width']],
            ['displayString' => 'Length', 'key' => 'length', 'value' => $data['length']],
            ['displayString' => 'Material', 'key' => 'material', 'value' => $data['material']],
            ['displayString' => 'Location', 'key' => 'location', 'value' => $data['display_location']],
            ['displayString' => 'Landmark', 'key' => 'landmark', 'value' => $data['display_land_mark']],
            ['displayString' => 'Latitude', 'key' => 'latitude', 'value' => $data['latitude']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Hoarding Category', 'key' => 'hoardingCategory', 'value' => $data['hoardingCategory']],
            ['displayString' => 'Illumination', 'key' => 'illumination', 'value' => $data['illumination']?"Yes":"No"],
            ['displayString' => 'Indicate Facing', 'key' => 'indicateFacing', 'value' => $data['indicate_facing']],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' =>  $data['zone_id']== '1'? 'A' :($data['zone_id']== '2'?'B' :($data['zone_id']== '3'?'C':'N/A'))],
        ]);
    }



    /**
     * | Get Card Details
     */
    public function generateHoardingCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data['application_no']],
            ['displayString' => 'License No', 'key' => 'applicantionNo', 'value' => $data['license_no']==NULL ? 'N/A' : $data['license_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y')],
        ]);
    }
}
