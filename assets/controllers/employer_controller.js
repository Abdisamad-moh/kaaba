import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    if (window.location.pathname.startsWith('/employer/postJob')) {
      this.findAnchorByHref();
      this.setNumberInputTypes();
    }
  }

  findAnchorByHref() {
    const targetHref = "#finish";
    const anchor = document.querySelector(`a[href="${targetHref}"]`);

    const parentElement = anchor.parentNode;

    const targetAnchor = document.querySelector('a[href="#next"]');
    if (targetAnchor) {
      targetAnchor.dataset.action = "click->hello#check"; // Add data-action attribute
      console.log("Added data-action attribute to anchor element:", targetAnchor);
    } else {
      console.log("Anchor tag with href='#next' not found.");
    }
    if (anchor) {
      const button = document.createElement("button");
      button.type = "submit";
      button.classList.add("btn", "btn-primary");
      button.textContent = "Save & Continue"; // Set button text

      anchor.parentNode.replaceChild(button, anchor);

      console.log("Anchor tag replaced with button:", button);
    } else {
      console.log("No anchor tag found with href", targetHref);
    }
  }

  setNumberInputTypes() {
    // Find all elements with the class "numberClass"
    const numberInputs = document.querySelectorAll('.numberClass');

    // Iterate over each element and change its type to "number"
    numberInputs.forEach(input => {
      input.setAttribute('type', 'number');
      console.log('Changed input type to number:', input);
    });
  }

  setCertification(event) {
    const certificationRequiredField = document.getElementById('job_form_certification_required');
    const certificationField = document.getElementById('job_form_certifications');
    console.log(certificationField)
    const selectedValue = event.target.value;
    if (selectedValue == 1) {
      certificationField.setAttribute("readonly", false);
      certificationField.removeAttribute("readonly");
    } else {
      certificationField.setAttribute("readonly", true);
    }
  }

  shortlistCandidate(event) {
    var candidate_id = event.currentTarget.dataset.can;
    var status = event.currentTarget.dataset.status;
    var type = event.currentTarget.dataset.type;
    
    if(type === "manual"){
      var label = "Manual Shortlist Candidate";
      var route = Routing.generate("app_employer_shortlist_manual_candidate", {
        candidate: candidate_id,
      });
      $("#approveModalBody").html(
        '<div class="p-15 text-center text-primary">Please wait...</div>'
      );
    }
    else if(type === "message"){
      var label = "Message Candidate";
      var route = Routing.generate("app_employer_message_candidate", {
        candidate: candidate_id,
      });
    } else if(type === "rejected"){
      var label = "Reject Candidate";
      var route = Routing.generate("app_employer_reject_candidate", {
        candidate: candidate_id,
      });
    }
    else{
      var label = "Shortlist Candidate";
      var route = Routing.generate("app_employer_shortlist_application", {
        candidate: candidate_id,
      });
      $("#approveModalBody").html(
        '<div class="p-15 text-center text-primary">Please wait...</div>'
      );
    }

    $("#approveLabel").html(label);
    $("#approveModal").modal("show");

    $.ajax({
      url: route,
      method: "POST",
      data: { data: "something" },
      success: function (data) {
        $("#approveModalBody").html(data);
        $("#approveModal").modal("show");
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        $("#approveModal").modal("hide");
      },
    });
  }

  scheduleInterview(event) {
    var type = event.currentTarget.dataset.type;
    var candidate_id = event.currentTarget.dataset.can;
    if (type === "resume") {
      var label = "Jobseeker Resume";
      var route = Routing.generate("app_employer_resume", {
        candidate: candidate_id,
      });
    }
    else if (type === "schedule interview") {
      var label = "Schedule Interview";
      var route = Routing.generate("app_employer_schedule_interview", {
        candidate: candidate_id,
      });
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
    else if (type === "view tender") {
      var label = "Show new tender/bid";
      var tender = event.currentTarget.dataset.tender;
      if(tender !== ""){
        var route = Routing.generate("app_employer_view_tender", {
          tender: tender,
        });
      }else {
        alert('Sorry No Tender Selected')
        return
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

  setType(event) {
    const type = event.currentTarget.dataset.type;
    $("#interview_form_type").val(type);

    const inPersonElements = [
      "interview_form_country",
      "interview_form_city",
      "interview_form_location"
    ];

    const virtualElement = "interview_form_meeting_link";
    if (type === "in person") {
      // Add required attribute to in-person elements sdfsdf
      for (const elementId of inPersonElements) {
        const element = document.getElementById(elementId);
        if (element) {
          element.setAttribute("required", false);

          console.log(element);
        } else {
          console.warn(`Element with id "${elementId}" not found.`);
        }
      }

      // Remove required attribute from virtual element (if it exists)
      const virtualElementNode = document.getElementById(virtualElement);
      if (virtualElementNode) {
        virtualElementNode.removeAttribute("required");
      }
    } else if (type === "virtual") {
      // Remove required attribute from in-person elements
      for (const elementId of inPersonElements) {
        const element = document.getElementById(elementId);
        if (element) {
          element.removeAttribute("required");
        }
      }

      // Add required attribute to virtual element
      const virtualElementNode = document.getElementById(virtualElement);
      if (virtualElementNode) {
        virtualElementNode.setAttribute("required", true);
      } else {
        console.warn(`Element with id "${virtualElement}" not found.`);
      }
    } else {
      console.warn(`Invalid interview type: "${type}"`);
    }
  }

  

  check(event) {
    const ul = document.querySelector('ul[role="menu"]');
    if (ul) {
      const secondLi = ul.querySelector('li:nth-child(2)');
      const thirdLi = ul.querySelector('li:nth-child(3)');

      if (secondLi && secondLi.style.display === 'none') {
        thirdLi.style.display = '';
        console.log("Third button element is shown.");
      } else {
        secondLi.style.display = 'none';
        console.log("Second list item is visible or no ul with role='menu' found.");
      }
    } else {
      console.log("No ul element with role='menu' found.");
    }
  }

  
}
