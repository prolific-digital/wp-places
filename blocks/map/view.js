// create a document ready function vanilla js
document.addEventListener("DOMContentLoaded", function () {
  // get .toggle-panel, .panel-content, and .filter-panel
  var filterPanel = document.querySelector(".filter-panel");
  var togglePanel = filterPanel.querySelector(".toggle-panel");
  var panelContent = filterPanel.querySelector(".panel-content");

  // add click event listener to togglePanel
  togglePanel.addEventListener("click", function () {
    // toggle the class 'open' on panelContent
    panelContent.classList.toggle("open");
  });
});
