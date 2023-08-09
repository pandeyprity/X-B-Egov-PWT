<?php

namespace App\Traits;

/**
 * Created On-26-07-2022 
 * Created For-Code Reusable for SelfAdvertisement
 */
trait SelfAdvertisement
{
    // Save and Updata function 
    public function storing($self_advertisement, $request)
    {
        $self_advertisement->license_year = $request->licenseYear;
        $self_advertisement->applicant = $request->applicant;
        $self_advertisement->father = $request->father;
        $self_advertisement->email = $request->email;
        $self_advertisement->residence_address = $request->residenceAddress;
        $self_advertisement->ward_no = $request->wardNo;
        $self_advertisement->permanent_address = $request->permanentAddress;
        $self_advertisement->mobile_no = $request->mobile;
        $self_advertisement->aadhar_no = $request->aadharNo;
        $self_advertisement->entity_name = $request->entityName;
        $self_advertisement->entity_address = $request->entityAddress;
        $self_advertisement->entity_ward = $request->wardNo1;
        $self_advertisement->installation_location = $request->installationLocation;
        $self_advertisement->brand_display_name = $request->brandDisplayName;
        $self_advertisement->holding_no = $request->holdingNo;
        $self_advertisement->trade_license_no = $request->tradeLicenseNo;
        $self_advertisement->gst_no = $request->gstNo;
        $self_advertisement->display_type = $request->displayType;
        $self_advertisement->display_area = $request->displayArea;
        $self_advertisement->longitude = $request->longitude;
        $self_advertisement->latitude = $request->latitude;
    }
}
