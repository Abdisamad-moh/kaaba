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
  connect() {

  }

  operation(event) {
    var type = event.currentTarget.dataset.type;
    var status = event.currentTarget.dataset.status;
    var account = event.currentTarget.dataset.account;
    if (type === "change status") {
     
      alert(account)
      var label = status + " Account";
      route = Routing.generate("app_admin_change_user_status", { email: account });
      route += "?status=" + encodeURIComponent(status);
    }
    else if (type === "verify") {
      var label = status + " Account";
      route = Routing.generate("app_admin_verify_user", { email: account });
      route += "?status=" + encodeURIComponent(status);
    }
    else if (type === "post tender") {
      var label = "Create a new tender/bid";
      var tender = event.currentTarget.dataset.tender;
      if(tender !== ""){
        var route = Routing.generate("app_employer_add_tender", {
          tender: tender,
        });
      }else{
        var route = Routing.generate("app_employer_add_tender");
      }
      
    }
    else if (type === "post course") {
      var label = "Create a new course";
      var course = event.currentTarget.dataset.course;
      if(course !== ""){
        var route = Routing.generate("app_employer_add_course", {
          course: course,
        });
      }else{
        var route = Routing.generate("app_employer_add_course");
      }
      
    }
    else if (type === "interview result") {
      var label = "Interview Result";
      var route = Routing.generate("app_employer_interview_result", {
        candidate: candidate_id,
      });
    }
    else if (type === "send offer") {
      var label = "Send Offer/Hired";
      var route = Routing.generate("app_employer_send_offer", {
        candidate: candidate_id,
      });
    }

    $("#generalModalBody").html(
      '<div class="p-15 text-center text-primary">Please wait...</div>'
    );
    $("#generalModalLabel").html(label);
    $("#generalModal").modal("show");

    $.ajax({
      url: route,
      method: "POST",
      data: { data: "something" },
      success: function (data) {
        $("#generalModalBody").html(data);
        $("#generalModal").modal("show");
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        $("#generalModal").modal("hide");
      },
    });
  }

}
