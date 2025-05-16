$(document).ready(function () {
    var isUpdate = $('#form_id').val() !== '';
    $("#timedoctorForm").validate({
        rules: {
            timedoctor_company_id: {
                required: true,
            },
            google_sheet_id: {
                required: true,
            },
            google_service_credentials: {
                required: !isUpdate
            }
        },
        messages: {
            timedoctor_company_id: {
                required: "Please enter your Timedoctor Company ID",
            },
            google_sheet_id: {
                required: "Please enter your Google Sheet ID",
            },
            google_service_credentials: {
                required: "Please upload Google Service Credentials (JSON)"
            }
        },
    });
});