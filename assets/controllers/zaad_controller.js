import { Controller } from '@hotwired/stimulus';


/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
  static targets = ["phone", "edahab"]
  connect() {

  }

  checkout(event) {
    // alert("Checkout")
    // const phone = this.phoneTarget.value;
    this.phone = this.phoneTarget;
    const telephone = this.phone.value;
    if (telephone === "") {
      alert("Please put a valid phone number");
      return false;
    }
    // const data = {
    //   "phone": telephone,
    // }
    var candidate_id = event.currentTarget.dataset.can;
    var type = event.currentTarget.dataset.type;
    const checkbox = document.getElementById("termsInput");
    if (!checkbox) {
      console.error("Checkbox element not found");
      return false;
    }
    if (!checkbox.checked) {
      alert("You have to agree the terms and conditions");
      return false;
    }
    var label = "Zaad Purchase";

    if (type === "jobseeker") {
      var route = Routing.generate("app_jobseeker_zaad_purchase", {
        package: candidate_id,
      });
    } else {
      var route = Routing.generate("app_employer_zaad_purchase", {
        package: candidate_id,
      });
    }




    $(".loader-div").show();

    // Simulate a 2-second delay
    //  const route = Routing.generate('create_employee_leave', {leave: leave_id});
    // $("#approveModalBody").html(
    //   '<div class="p-15 text-center text-primary">Please wait...</div>'
    // );
    // $("#approveLabel").html(label);
    // $("#approveModal").modal("show");

    $.ajax({
      url: route,
      method: "POST",
      data: {
        package: candidate_id,
        telephone: telephone
      },
      success: function (data) {
        if (data.redirectUrl) {
          window.location.href = data.redirectUrl;
          $(".loader-div").hide();
        } else {
          $(".loader-div").hide();
          $("#al-danger-alert").modal("show");
        }

        // $("#al-danger-alert").modal("show");
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        // $("#approveModal").modal("hide");
        // console.log(data);
        // $(".loader-div").hide();
        // $("#al-danger-alert").modal("show");
      },
    });
  }
  checkout_edahab(event) {
    // alert("edahab")
    // const phone = this.phoneTarget.value;
    this.phone = this.edahabTarget;
    const telephone = this.phone.value;
    if (telephone === "") {
      alert("Please put a valid phone number");
      return false;
    }
    // const data = {
    //   "phone": telephone,
    // }
    var candidate_id = event.currentTarget.dataset.can;
    var type = event.currentTarget.dataset.type;
    const checkbox = document.getElementById("termsInputEdahab");
    if (!checkbox) {
      console.error("Checkbox element not found");
      return false;
    }
    if (!checkbox.checked) {
      alert("You have to agree the terms and conditions");
      return false;
    }
    var label = "Zaad Purchase";

    if (type === "jobseeker") {

      var route = Routing.generate("app_jobseeker_edahab_purchase", {
        package: candidate_id,
      });
    } else {
      var route = Routing.generate("app_employer_edahab_purchase", {
        package: candidate_id,
      });
    }
    $(".loader-div").show();
    $.ajax({
      url: route,
      method: "POST",
      data: {
        package: candidate_id,
        telephone: telephone
      },
      success: function (data) {
        if (data.redirectUrl) {
          window.location.href = data.redirectUrl;
          $(".loader-div").hide();
        } else {
          $(".loader-div").hide();
          $("#al-danger-alert").modal("show");
        }

        // $("#al-danger-alert").modal("show");
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
      },
    });
  }

}
