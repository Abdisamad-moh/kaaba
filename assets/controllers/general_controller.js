import { Controller } from "@hotwired/stimulus";
import { Modal } from 'bootstrap';
export default class extends Controller {
  static targets = ['opd'];

  connect() {
    // Any setup needed when the controller connects
  }

  printIt(event) {
    const group = event.currentTarget.dataset.group;
    let route;
  
    if (group === "order") {
      const orderId = event.currentTarget.dataset.item;
      const type = event.currentTarget.dataset.type;
      route = Routing.generate('app_misc_print_order', { order: orderId, type: type });
    } else if (group === "bill") {
      const orderId = event.currentTarget.dataset.item;
      const type = event.currentTarget.dataset.type;
      route = Routing.generate('app_misc_print_bill', { order: orderId, type: type });
    }
  
    $.ajax({
      url: route,
      method: 'POST',
      data: {},
      success: function (data) {
        // Create a new iframe dynamically
        const iframe = document.createElement('iframe');
        iframe.id = 'print-iframe';
        iframe.style.display = 'none'; // Hide the iframe
  
        // Append the iframe to the body
        document.body.appendChild(iframe);
  
        // Access the iframe's document
        const iframeDoc = iframe.contentWindow || iframe.contentDocument.document || iframe.contentDocument;
  
        // Write the received data into the iframe
        iframeDoc.document.open();
        iframeDoc.document.write(data);
        iframeDoc.document.close();
  
        // Wait for the iframe to load and then print
        iframe.onload = function () {
          iframe.contentWindow.focus(); // Focus the iframe
          iframe.contentWindow.print(); // Trigger the print
  
          // Remove the iframe after printing (optional)
          document.body.removeChild(iframe);
        };
      },
      error: function (xhr, status, error) {
        alert('Something went wrong! Try refreshing the page.');
        console.log(error);
      }
    });
  }

  

  operations(event) {
    var type = event.currentTarget.dataset.type;
    var label;
    var route;

    if (type === "operation") {
      label = "Edit Operation";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_operation", { operation: item });
    } else if (type === "new operation") {
      label = "Register Operation";
      route = Routing.generate("new_operation");
    } else if (type === "delete operation") {
      // Add your delete operation code here
    } else if (type === "new delivery") {
      label = "Register Delivery";
      route = Routing.generate("new_delivery");
    } else if (type === "delivery") {
      label = "Edit Delivery";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_delivery", { delivery: item });
    } else if (type === "new product") {
      label = "New Product";
      route = Routing.generate("new_product");
    } else if (type === "product") {
      label = "Edit Product";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_product", { product: item });
    } else if (type === "vendor") {
      label = "Edit vendor";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_vendor", { vendor: item });
    } else if (type === "new vendor") {
      label = "New vendor";
      route = Routing.generate("new_vendor");
    } else if (type === "customer") {
      label = "Edit customer";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_customer", { customer: item });
    } else if (type === "new customer") {
      label = "New customer";
      route = Routing.generate("new_customer");
    } else if (type === "account") {
      label = "Edit account";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("new_account", { account: item });
    } else if (type === "new account") {
      label = "New account";
      route = Routing.generate("new_account");
    } else if (type === "update product unit") {
      label = "Edit Product Unit of measurements";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("update_product_unit", { product: item });
    }else if (type === "disable member") {
      label = "Disabling Member";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("app_accounting_customer_disable", { id: item });

    }else if (type === "activate member") {
      label = "Activating Member";
      var item = event.currentTarget.dataset.item;
      route = Routing.generate("app_accounting_customer_activate", { id: item });
    }else if (type === "get logs") {
  label = "Application Logs";
  var url = event.currentTarget.dataset.url; // ✅ Use the pre-generated URL
  route = url;
}
else if (type === "update order items") {
      label = "Update Order Items";
      var item = event.currentTarget.dataset.item;
      // var url = event.currentTarget.dataset.url;
      route = Routing.generate("app_orders_update_details", { order: item });
    }

    // Set the modal label and loading state
    const modalBody = document.getElementById("generalModalBody");
    const modalLabel = document.getElementById("generalModalLabel");

    modalBody.innerHTML = '<div class="p-15 text-center text-primary">Please wait...</div>';
    modalLabel.innerHTML = label;

    // Show modal using Bootstrap Modal API
    const modalElement = document.getElementById("generalModal");
    // const modal = new bootstrap.Modal(modalElement);
    const modal = Modal.getOrCreateInstance(modalElement);
    modal.show();

    // Make the AJAX request
    fetch(route, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ data: "something" }),
    })
      .then(response => response.text())
      .then(data => {
        modalBody.innerHTML = data; // Update the modal content
      })
      .catch(error => {
        console.error("Error:", error);
        modal.hide(); // Hide modal on error
      });
  }
}
