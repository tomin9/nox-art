(function(){
  document.addEventListener("DOMContentLoaded", function(){
    var el = document.getElementById("nox-admin-picker");
    var latInput = document.getElementById("nox_lat");
    var lngInput = document.getElementById("nox_lng");
    if (!el || !latInput || !lngInput || typeof L === "undefined") return;

    var hasCoords = latInput.value && lngInput.value;
    var start = hasCoords
      ? [parseFloat(latInput.value), parseFloat(lngInput.value)]
      : [48.7715, 18.6045];

    var map = L.map(el).setView(start, hasCoords ? 16 : 13);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "© OpenStreetMap"
    }).addTo(map);

    var marker = hasCoords ? L.marker(start, {draggable: true}).addTo(map) : null;

    function setCoords(latlng){
      latInput.value = latlng.lat.toFixed(6);
      lngInput.value = latlng.lng.toFixed(6);
    }
    function placeMarker(latlng){
      if (!marker) {
        marker = L.marker(latlng, {draggable: true}).addTo(map);
        marker.on("dragend", function(){ setCoords(marker.getLatLng()); });
      } else {
        marker.setLatLng(latlng);
      }
      setCoords(latlng);
    }
    if (marker) marker.on("dragend", function(){ setCoords(marker.getLatLng()); });

    map.on("click", function(e){ placeMarker(e.latlng); });

    // Ak sa modálne okno/metabox zobrazí až po výpočte rozmerov, mapa môže
    // mať pri prvom vykreslení nesprávnu veľkosť dlaždíc.
    setTimeout(function(){ map.invalidateSize(); }, 200);
  });
})();
