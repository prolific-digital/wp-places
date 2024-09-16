document.addEventListener("DOMContentLoaded", function () {
  // get every isntance of .block-map
  var maps = document.querySelectorAll(".block-map");

  // loop through each instance
  maps.forEach(function (map) {
    // get the .toggle-panel, and .facet.panel
    var toggle = map.querySelector(".toggle-panel");
    var panel = map.querySelector(".facet.panel");

    // when the toggle is clicked, toggle the class 'open' on the panel
    toggle.addEventListener("click", function () {
      panel.classList.toggle("open");
    });
  });
});
