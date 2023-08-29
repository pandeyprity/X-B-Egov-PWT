<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Created for Gettting Dynamic Saf Details
 */
trait MarDetailsTraits
{
    /**
     * | Get Basic Details
     */
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Father Name', 'key' => 'fatherName', 'value' => $data['father']],
            ['displayString' => 'Rule', 'key' => 'rule', 'value' => $data['rule']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residential Address', 'key' => 'residentialAddress', 'value' => $data['residential_address']],
            ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['licenseYear']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'Floor Area', 'key' => 'floorArea', 'value' => $data['floor_area']],
            ['displayString' => 'Aadhar Card', 'key' => 'aadharCard', 'value' => $data['aadhar_card']],
            ['displayString' => 'Pan Card', 'key' => 'panCard', 'value' => $data['pan_card']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'Ward NO', 'key' => 'wardNo', 'value' => $data['ward_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentwardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entitywardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'Hall Type', 'key' => 'hallType', 'value' => $data['halltype']],
            ['displayString' => 'Organization Type', 'key' => 'organizationType', 'value' => $data['organizationtype']],
            ['displayString' => 'Remarks', 'key' => 'remarks', 'value' => $data['remarks']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'No Of CCTV', 'key' => 'noOfCctv', 'value' => $data['cctv_camera']],
            ['displayString' => 'No of Fire Extinguisher', 'key' => 'fireExtinguisher', 'value' => $data['fire_extinguisher']],
            ['displayString' => 'No of Entry Gate', 'key' => 'entryGate', 'value' => $data['entry_gate']],
            ['displayString' => 'No of two Wheelers Parking', 'key' => 'twoWheelersParking', 'value' => $data['two_wheelers_parking']],
            ['displayString' => 'No of four Wheelers Parking', 'key' => 'fourWheelersParking', 'value' => $data['four_wheelers_parking']],
            ['displayString' => 'Security Type', 'key' => 'securityType', 'value' => $data['securitytype']],
            ['displayString' => 'Electricity Type', 'key' => 'electricityType', 'value' => $data['electricitytype']],
            ['displayString' => 'Water Supply Type', 'key' => 'waterSupplyType', 'value' => $data['watersupplytype']],
            ['displayString' => 'Land Deed Type', 'key' => 'landDeedType', 'value' => $data['landDeedType']],
        ]);
    }

    

       /**
     * | Get Basic Details
     */
    public function generateBasicDetailsForHostel($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Father Name', 'key' => 'fatherName', 'value' => $data['father']],
            ['displayString' => 'Rule', 'key' => 'rule', 'value' => $data['rule']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residential Address', 'key' => 'residentialAddress', 'value' => $data['residential_address']],
            ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['m_license_year']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'No of Rooms', 'key' => 'noOfRooms', 'value' => $data['no_of_rooms']],
            ['displayString' => 'No of Beds', 'key' => 'noOfBeds', 'value' => $data['no_of_beds']],
            ['displayString' => 'Aadhar Card', 'key' => 'aadharCard', 'value' => $data['aadhar_card']],
            ['displayString' => 'Pan Card', 'key' => 'panCard', 'value' => $data['pan_card']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $data['ward_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentwardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entitywardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'Organization Type', 'key' => 'organizationType', 'value' => $data['organizationtype']],
            ['displayString' => 'Land Deed Type', 'key' => 'landDeedType', 'value' => $data['landDeedTypeName']],
            ['displayString' => 'Mess Type', 'key' => 'messType', 'value' => $data['messtype']],
            ['displayString' => 'Hostel Type', 'key' => 'hosteltype', 'value' => $data['hosteltype']],
            // ['displayString' => 'Hostel Type', 'key' => 'hosteltype', 'value' => $data['hosteltype']],
            ['displayString' => 'License Year', 'key' => 'licenseYear', 'value' => $data['m_license_year']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Remarks', 'key' => 'remarks', 'value' => $data['remarks']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'Water Supply Type', 'key' => 'waterSupplyType', 'value' => $data['waterSupplyType']],
            ['displayString' => 'Electricity Type', 'key' => 'electricityType', 'value' => $data['electricityType']],
            ['displayString' => 'Security Type', 'key' => 'securityType', 'value' => $data['securityType']],
            ['displayString' => 'License No', 'key' => 'licenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Approved By Govt', 'key' => 'isApproveByGovt', 'value' => $data['is_approve_by_govt']==true?'Yes':'No'],
            ['displayString' => 'No of CCTV', 'key' => 'noOfCCTV', 'value' => $data['cctv_camera']],
            ['displayString' => 'No of Fire Extinguisher', 'key' => 'NoOfFireExtinguisher', 'value' => $data['fire_extinguisher']],
            ['displayString' => 'No of Entry Gate', 'key' => 'entryGate', 'value' => $data['entry_gate']],
            ['displayString' => 'No of Exit Gate', 'key' => 'exitGate', 'value' => $data['exit_gate']],
        ]);
    }


    
       /**
     * | Get Basic Details
     */
    public function generateBasicDetailsForLodge($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Father Name', 'key' => 'fatherName', 'value' => $data['father']],
            ['displayString' => 'Rule', 'key' => 'rule', 'value' => $data['rule']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residential Address', 'key' => 'residentialAddress', 'value' => $data['residential_address']],
            ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['m_license_year']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'No of Rooms', 'key' => 'noOfRooms', 'value' => $data['no_of_rooms']],
            ['displayString' => 'No of Beds', 'key' => 'noOfBeds', 'value' => $data['no_of_beds']],
            ['displayString' => 'Aadhar Card', 'key' => 'aadharCard', 'value' => $data['aadhar_card']],
            ['displayString' => 'Pan Card', 'key' => 'panCard', 'value' => $data['pan_card']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'Ward NO', 'key' => 'wardNo', 'value' => $data['ward_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentwardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entitywardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'Organization Type', 'key' => 'organizationType', 'value' => $data['organizationtype']],
            ['displayString' => 'Water Supply Type', 'key' => 'waterSupplyType', 'value' => $data['watersupplytype']],
            ['displayString' => 'Electricity Type', 'key' => 'electricityType', 'value' => $data['electricitytype']],
            ['displayString' => 'Security Type', 'key' => 'securityType', 'value' => $data['securitytype']],
            ['displayString' => 'Mess Type', 'key' => 'messType', 'value' => $data['messtype']],
            ['displayString' => 'Lodge Type', 'key' => 'lodgeType', 'value' => $data['lodgetype']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
        ]);
    }

        /**
     * | Get Basic Details
     */
    public function generateBasicDetailsforDharamshala($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $data['license_no']],
            ['displayString' => 'Father Name', 'key' => 'fatherName', 'value' => $data['father']],
            ['displayString' => 'Rule', 'key' => 'rule', 'value' => $data['rule']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residential Address', 'key' => 'residentialAddress', 'value' => $data['residential_address']],
            ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['m_license_year']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'Floor Area', 'key' => 'floorArea', 'value' => $data['floor_area']],
            ['displayString' => 'Aadhar Card', 'key' => 'aadharCard', 'value' => $data['aadhar_card']],
            ['displayString' => 'Pan Card', 'key' => 'panCard', 'value' => $data['pan_card']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'Ward NO', 'key' => 'wardNo', 'value' => $data['ward_no']],
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentwardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entitywardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'Organization Type', 'key' => 'organizationType', 'value' => $data['organizationtype']],
            ['displayString' => 'Land Deed Type', 'key' => 'landDeedType', 'value' => $data['landDeedType']],
            ['displayString' => 'Water Supply Type', 'key' => 'waterSupplyType', 'value' => $data['watersupplytype']],
            ['displayString' => 'Security Type', 'key' => 'Security Type', 'value' => $data['securitytype']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Remarks', 'key' => 'remarks', 'value' => $data['remarks']],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            ['displayString' => 'No Of CCTV', 'key' => 'noOfCctv', 'value' => $data['cctv_camera']],
            ['displayString' => 'No of Fire Extinguisher', 'key' => 'fireExtinguisher', 'value' => $data['fire_extinguisher']],
            ['displayString' => 'No of Entry Gate', 'key' => 'entryGate', 'value' => $data['entry_gate']],
            ['displayString' => 'No of two Wheelers Parking', 'key' => 'twoWheelersParking', 'value' => $data['two_wheelers_parking']],
            ['displayString' => 'No of four Wheelers Parking', 'key' => 'fourWheelersParking', 'value' => $data['four_wheelers_parking']],
        ]);
    }


    /**
     * | Get Card Details
     */
    public function generateCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'Appication No', 'key' => 'appicationNo', 'value' => $data['application_no']],
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
            ['displayString' => 'Appication No', 'key' => 'applicantNo', 'value' => $data->application_no],
            ['displayString' => 'Appication Date', 'key' => 'appicationDate', 'value' => $data->application_date],
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
            ['displayString' => 'Permanent Ward No', 'key' => 'permanentWardNo', 'value' => $data['permanent_ward_no']],
            ['displayString' => 'Entity Ward No', 'key' => 'entityWardNo', 'value' => $data['entity_ward_no']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Ward ID', 'key' => 'wardId', 'value' => $data['ward_id']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Aadhar No', 'key' => 'aadharNo', 'value' => $data['aadhar_no']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'M Display Name', 'key' => 'mDisplayType', 'value' => $data['m_display_type']],
            ['displayString' => 'Vehicle No', 'key' => 'vehicleNo', 'value' => $data['vehicle_no']],
            ['displayString' => 'Vehicle Name', 'key' => 'vehicleName', 'value' => $data['vehicle_name']],
            ['displayString' => 'Front Area', 'key' => 'frontArea', 'value' => $data['front_area']],
            ['displayString' => 'Rear Area', 'key' => 'rearArea', 'value' => $data['rear_area']],
            ['displayString' => 'Side Area', 'key' => 'sideArea', 'value' => $data['side_area']],
            ['displayString' => 'Top Area', 'key' => 'topArea', 'value' => $data['top_area']],
        ]);
    }


    /**
     * | Get Vehicle Card Details
     */
    public function generateVehicleCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Applicant Name', 'key' => 'applicantName', 'value' => $data['applicant']],
            ['displayString' => 'Appication No', 'key' => 'appicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
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
            ['displayString' => 'Appication No', 'key' => 'appicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Appication Date', 'key' => 'appicationDate', 'value' => $data['application_date']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Address', 'key' => 'address', 'value' => $data['address']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Fax', 'key' => 'fax', 'value' => $data['fax']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Pan No', 'key' => 'panNo', 'value' => $data['pan_no']],
            ['displayString' => 'Blacklisted', 'key' => 'blacklisted', 'value' => $data['blacklisted'] == 0 ? "NO" : "YES"],
            ['displayString' => 'Pending Amount', 'key' => 'pendingAmount', 'value' => $data['pending_amount']],
            ['displayString' => 'pending Cour tCase', 'key' => 'pendingCourtCase', 'value' => $data['pending_court_case'] == 0 ? "NO" : "YES"],
        ]);
    }


    /**
     * | Get Agency Card Details
     */
    public function generateAgencyCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Appication No', 'key' => 'appicationNo', 'value' => $data['application_no']],
            ['displayString' => 'Appication Date', 'key' => 'appicationDate', 'value' => $data['application_date']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Pending Amount', 'key' => 'pending_amount', 'value' => $data['pending_amount']],
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
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => $data['application_date']],
            ['displayString' => 'Applicant', 'key' => 'applicant', 'value' => $data['applicant']],
            ['displayString' => 'ULB Name', 'key' => 'ulbName', 'value' => $data['ulb_name']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Father', 'key' => 'father', 'value' => $data['father']],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            ['displayString' => 'Ward ID', 'key' => 'wardId', 'value' => $data['ward_id']],
            ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile_no']],
            ['displayString' => 'Aadhar No', 'key' => 'aadharNo', 'value' => $data['aadhar_no']],
            ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            ['displayString' => 'Licence From', 'key' => 'lLicenseFrom', 'value' => $data['license_from']],
            ['displayString' => 'Licence To', 'key' => 'lLicenseTo', 'value' => $data['license_to']],
            ['displayString' => 'No Of Hording', 'key' => 'noOfHording', 'value' => $data['no_of_hoardings']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            ['displayString' => 'GST No', 'key' => 'gstNo', 'value' => $data['gst_no']],
            ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Brand Display Name', 'key' => 'brandDisplayName', 'value' => $data['brand_display_name']]
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
            ['displayString' => 'Appication Date', 'key' => 'appicationDate', 'value' => $data['application_date']],
            ['displayString' => 'Residence Address', 'key' => 'residenceAddress', 'value' => $data['residence_address']],
            ['displayString' => 'Permanent Address', 'key' => 'permanentAddress', 'value' => $data['permanent_address']],
            ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
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
    public function generatehordingLicenseDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicantionNo', 'value' => $data['application_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => $data['application_date']],
            ['displayString' => 'Permit No', 'key' => 'permitNo', 'value' => $data['permit_no']],
            ['displayString' => 'Road Street/Address', 'key' => 'roadStreetAddress', 'value' => $data['road_street_address']],
            ['displayString' => 'Date Granted', 'key' => 'dateGranted', 'value' => $data['date_granted']],
            ['displayString' => 'Permit Date Issue', 'key' => 'permitDateIssue', 'value' => $data['permit_date_issue']],
            ['displayString' => 'Permit Expired Issue', 'key' => 'permitExpiredIssue', 'value' => $data['permit_expired_issue']],
            ['displayString' => 'Account No', 'key' => 'accountNo', 'value' => $data['account_no']],
            ['displayString' => 'Bank Name', 'key' => 'bankName', 'value' => $data['bank_name']],
            ['displayString' => 'IFSC Code', 'key' => 'ifscCode', 'value' => $data['ifsc_code']],
            ['displayString' => 'Total Charge', 'key' => 'totalCharge', 'value' => $data['total_charge']],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $data['property_type']],
            ['displayString' => 'Property Owner Name', 'key' => 'propertyOwnerName', 'value' => $data['property_owner_name']],
            ['displayString' => 'Property Owner Address', 'key' => 'propertyOwnerAddress', 'value' => $data['property_owner_address']],
            ['displayString' => 'Property Owner Pincode', 'key' => 'propertyOwnerPincode', 'value' => $data['property_owner_pincode']],
            ['displayString' => 'Property Owner Mobile No', 'key' => 'propertyOwnerMobileNo', 'value' => $data['property_owner_mobile_no']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Display Location', 'key' => 'displayLocation', 'value' => $data['display_location']],
            ['displayString' => 'Display Street', 'key' => 'displayStreet', 'value' => $data['display_street']],
            ['displayString' => 'Display Land Mark', 'key' => 'displayLandMark', 'value' => $data['display_land_mark']],
            ['displayString' => 'Display Area', 'key' => 'displayArea', 'value' => $data['display_area']],
            ['displayString' => 'Heigth', 'key' => 'heigth', 'value' => $data['heigth']],
            ['displayString' => 'Length', 'key' => 'length', 'value' => $data['length']],
            ['displayString' => 'Size', 'key' => 'size', 'value' => $data['size']],
            ['displayString' => 'Material', 'key' => 'material', 'value' => $data['material']],
            ['displayString' => 'Illumination', 'key' => 'illumination', 'value' => $data['illumination']?"Yes":"No"],
            ['displayString' => 'Indicate_facing', 'key' => 'indicate_facing', 'value' => $data['indicate_facing']],
            ['displayString' => 'License No', 'key' => 'licenseNo', 'value' => $data['license_no']]
        ]);
    }



    /**
     * | Get Card Details
     */
    public function generateLiceasneCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicantionNo', 'value' => $data['application_no']],
            ['displayString' => 'Application Date', 'key' => 'applicationDate', 'value' => $data['application_date']],
            ['displayString' => 'Permit No', 'key' => 'permitNo', 'value' => $data['permit_no']],
            ['displayString' => 'Road Street/Address', 'key' => 'roadStreetAddress', 'value' => $data['road_street_address']],
            ['displayString' => 'Date Granted', 'key' => 'dateGranted', 'value' => $data['date_granted']],
            ['displayString' => 'Permit Date Issue', 'key' => 'permitDateIssue', 'value' => $data['permit_date_issue']],
            ['displayString' => 'Permit Expired Issue', 'key' => 'permitExpiredIssue', 'value' => $data['permit_expired_issue']],
            ['displayString' => 'Account No', 'key' => 'accountNo', 'value' => $data['account_no']],
            ['displayString' => 'Bank Name', 'key' => 'bankName', 'value' => $data['bank_name']],
            ['displayString' => 'IFSC Code', 'key' => 'ifscCode', 'value' => $data['ifsc_code']],
            ['displayString' => 'Total Charge', 'key' => 'totalCharge', 'value' => $data['total_charge']],
        ]);
    }
}
