(function(){
"use strict";

var DATA = (typeof NOX_ART_DATA !== "undefined") ? NOX_ART_DATA : {miesta:[], diela:[], umelci:[], program:[]};

var appRoot = document.querySelector(".nox-art-app");
if (!appRoot) return;
var panel = appRoot.querySelector("#na-panel");
var detail = appRoot.querySelector("#na-detail");

var esc = function(s){ return String(s == null ? "" : s).replace(/[&<>"]/g, function(c){
  return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c];
}); };

var DEN_NAZVY = ["nedeľa","pondelok","utorok","streda","štvrtok","piatok","sobota"];
function formatDatum(d){
  if (!d) return "";
  var parts = d.split("-");
  if (parts.length !== 3) return d;
  var dt = new Date(parts[0]+"-"+parts[1]+"-"+parts[2]+"T00:00:00");
  if (isNaN(dt.getTime())) return d;
  return DEN_NAZVY[dt.getDay()] + " " + parts[2] + ". " + parts[1] + ". " + parts[0];
}

/* ---------------------------------------------------------------------
   Lookupy
   --------------------------------------------------------------------- */
function miestoById(id){ return DATA.miesta.find(function(m){ return m.id == id; }); }
function umelecById(id){ return DATA.umelci.find(function(u){ return u.id == id; }); }
function dieloById(id){ return DATA.diela.find(function(d){ return d.id == id; }); }
function dielaByMiesto(id){ return DATA.diela.filter(function(d){ return d.miestoId == id; }); }
function dielaByUmelec(id){ return DATA.diela.filter(function(d){ return d.umelecId == id; }); }

/* ---------------------------------------------------------------------
   Stav a routovanie (#/tab alebo #/tab/id)
   --------------------------------------------------------------------- */
var S = { tab: "mapa", id: null };

function parseHash(){
  var m = location.hash.match(/^#\/(mapa|diela|umelci|program)(?:\/([^/]+))?/);
  if (m) S = { tab: m[1], id: m[2] || null };
  else S = { tab: "mapa", id: null };
}
function updateHash(){
  var h = "#/" + S.tab + (S.id ? ("/" + S.id) : "");
  if (location.hash !== h){ try { location.hash = h; } catch(_){} }
}
function goTab(tab){ S = { tab: tab, id: null }; updateHash(); render(); }
function goDetail(tab, id){ S = { tab: tab, id: String(id) }; updateHash(); render(); if (detail) detail.scrollTop = 0; }

window.addEventListener("hashchange", function(){ parseHash(); render(); });

function wireTabs(){
  appRoot.querySelectorAll("[data-tab]").forEach(function(btn){
    btn.onclick = function(){ goTab(btn.dataset.tab); };
  });
}
function updateTabs(){
  appRoot.querySelectorAll("[data-tab]").forEach(function(btn){
    btn.setAttribute("aria-pressed", btn.dataset.tab === S.tab ? "true" : "false");
  });
}

/* Delegovanie kliknutí na položky vygenerované cez innerHTML. */
function wireGo(root){
  root.querySelectorAll("[data-go-tab]").forEach(function(el){
    el.onclick = function(){ goDetail(el.dataset.goTab, el.dataset.goId); };
  });
}

/* ---------------------------------------------------------------------
   Mapa (Leaflet)
   --------------------------------------------------------------------- */
var map = null;
var markers = {};

function initMap(){
  var mapEl = document.getElementById("na-map");
  if (!mapEl || typeof L === "undefined") return;
  map = L.map(mapEl, { scrollWheelZoom: false });
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  var icon = L.divIcon({ className: "na-marker", iconSize: [22,22], iconAnchor: [11,22] });
  var pts = [];
  DATA.miesta.forEach(function(m){
    if (m.lat == null || m.lng == null) return;
    var mk = L.marker([m.lat, m.lng], { icon: icon }).addTo(map);
    mk.bindPopup("<strong>" + esc(m.nazov) + "</strong>" + (m.adresa ? "<br>" + esc(m.adresa) : ""));
    mk.on("click", function(){ goDetail("mapa", m.id); });
    markers[m.id] = mk;
    pts.push([m.lat, m.lng]);
  });

  if (pts.length){
    map.fitBounds(pts, { padding: [50,50], maxZoom: 16 });
  } else {
    map.setView([48.7715, 18.6045], 13);
  }
  setTimeout(function(){ map.invalidateSize(); }, 200);
}
function focusMarker(miestoId){
  if (!map) return;
  var mk = markers[miestoId];
  if (!mk) return;
  map.flyTo(mk.getLatLng(), Math.max(map.getZoom(), 16), { duration: 0.6 });
  mk.openPopup();
}

/* ---------------------------------------------------------------------
   Vykresľovanie – MAPA (zoznam miest v paneli, detail miesta v detaile)
   --------------------------------------------------------------------- */
function crumb(parts){
  return '<nav class="crumb">' + parts.map(function(p, i){
    var sep = i ? '<span>›</span>' : '';
    if (p.go) return sep + '<a tabindex="0" data-go-tab="' + esc(p.go[0]) + '" data-go-id="' + esc(p.go[1]||"") + '">' + esc(p.t) + '</a>';
    return sep + '<span>' + esc(p.t) + '</span>';
  }).join('') + '</nav>';
}

function renderMapaPanel(){
  var n = DATA.miesta.length;
  panel.innerHTML = crumb([{t:"mapa"}]) + '<div class="pad">'
    + '<p class="eyebrow">NOX:ART</p>'
    + '<h2 class="title">Miesta<br>festivalu</h2>'
    + '<p class="lead">Vyber miesto na mape alebo v zozname a pozri si, ktoré diela tam môžeš vidieť.</p>'
    + '<h3 class="sec">Miesta <span class="kod">' + n + '</span></h3>'
    + (n ? '<ul class="list">' + DATA.miesta.map(function(m){
        var pocet = dielaByMiesto(m.id).length;
        return '<li><button class="fitem" data-go-tab="mapa" data-go-id="' + esc(m.id) + '">'
          + '<span class="fname">' + esc(m.nazov) + '</span>'
          + '<span class="fdir">' + (m.adresa ? esc(m.adresa) + " · " : "") + pocet + (pocet===1?" dielo":" diel") + '</span>'
          + '</button></li>';
      }).join('') + '</ul>' : '<div class="empty">Zatiaľ nie sú pridané žiadne miesta.</div>')
    + '</div>';
  wireGo(panel);
}

function renderMiestoDetail(m){
  var diela = dielaByMiesto(m.id);
  detail.innerHTML = crumb([{t:"mapa", go:["mapa",null]}, {t:m.nazov}]) + '<div class="pad">'
    + (m.foto ? '<img class="detail-photo" src="' + esc(m.foto) + '" alt="">' : '')
    + '<p class="eyebrow">Miesto</p>'
    + '<h2 class="title">' + esc(m.nazov) + '</h2>'
    + (m.adresa ? '<p class="lead">' + esc(m.adresa) + '</p>' : '')
    + (m.popis ? '<div class="prose" style="margin-top:14px">' + m.popis + '</div>' : '')
    + '<h3 class="sec">Diela na tomto mieste <span class="kod">' + diela.length + '</span></h3>'
    + (diela.length ? '<ul class="list">' + diela.map(function(d){
        var u = umelecById(d.umelecId);
        return '<li><button class="fitem" data-go-tab="diela" data-go-id="' + esc(d.id) + '">'
          + '<span class="fname">' + esc(d.nazov) + '</span>'
          + '<span class="fdir">' + (u ? esc(u.meno) : "") + '</span>'
          + '</button></li>';
      }).join('') + '</ul>' : '<div class="empty">Zatiaľ tu nie je priradené žiadne dielo.</div>')
    + '</div>';
  wireGo(detail);
}

/* ---------------------------------------------------------------------
   Vykresľovanie – DIELA
   --------------------------------------------------------------------- */
function renderDielaList(){
  var n = DATA.diela.length;
  panel.innerHTML = crumb([{t:"diela"}]) + '<div class="pad">'
    + '<p class="eyebrow">Katalóg</p>'
    + '<h2 class="title">Diela <span class="kod">' + n + '</span></h2>'
    + (n ? '<ul class="list">' + DATA.diela.map(function(d){
        var u = umelecById(d.umelecId), m = miestoById(d.miestoId);
        return '<li><button class="fitem" data-go-tab="diela" data-go-id="' + esc(d.id) + '">'
          + '<span class="fname">' + esc(d.nazov) + '</span>'
          + '<span class="fdir">' + (u ? esc(u.meno) : "") + (u && m ? " · " : "") + (m ? esc(m.nazov) : "") + '</span>'
          + '</button></li>';
      }).join('') + '</ul>' : '<div class="empty">Zatiaľ žiadne diela.</div>')
    + '</div>';
  wireGo(panel);
}

function renderDieloDetail(d){
  var u = umelecById(d.umelecId), m = miestoById(d.miestoId);
  detail.innerHTML = crumb([{t:"diela", go:["diela",null]}, {t:d.nazov}]) + '<div class="pad">'
    + (d.foto ? '<img class="detail-photo" src="' + esc(d.foto) + '" alt="">' : '')
    + '<p class="eyebrow">Dielo</p>'
    + '<h2 class="title">' + esc(d.nazov) + '</h2>'
    + '<table class="meta">'
    + (u ? '<tr><th>Umelec/umelkyňa</th><td><a data-go-tab="umelci" data-go-id="' + esc(u.id) + '" href="javascript:void(0)">' + esc(u.meno) + '</a></td></tr>' : '')
    + (m ? '<tr><th>Miesto</th><td><a data-go-tab="mapa" data-go-id="' + esc(m.id) + '" href="javascript:void(0)">' + esc(m.nazov) + '</a></td></tr>' : '')
    + '</table>'
    + (d.popis ? '<div class="prose" style="margin-top:14px">' + d.popis + '</div>' : '')
    + '</div>';
  wireGo(detail);
}

/* ---------------------------------------------------------------------
   Vykresľovanie – UMELCI
   --------------------------------------------------------------------- */
function renderUmelciList(){
  var n = DATA.umelci.length;
  panel.innerHTML = crumb([{t:"umelci"}]) + '<div class="pad">'
    + '<p class="eyebrow">Festival</p>'
    + '<h2 class="title">Umelci <span class="kod">' + n + '</span></h2>'
    + (n ? '<ul class="list">' + DATA.umelci.map(function(u){
        var pocet = dielaByUmelec(u.id).length;
        return '<li><button class="fitem" data-go-tab="umelci" data-go-id="' + esc(u.id) + '">'
          + '<span class="fname">' + esc(u.meno) + '</span>'
          + '<span class="fdir">' + pocet + (pocet===1?" dielo":" diel") + '</span>'
          + '</button></li>';
      }).join('') + '</ul>' : '<div class="empty">Zatiaľ žiadni umelci.</div>')
    + '</div>';
  wireGo(panel);
}

function renderUmelecDetail(u){
  var diela = dielaByUmelec(u.id);
  detail.innerHTML = crumb([{t:"umelci", go:["umelci",null]}, {t:u.meno}]) + '<div class="pad">'
    + (u.foto ? '<img class="detail-photo" src="' + esc(u.foto) + '" alt="">' : '')
    + '<p class="eyebrow">Umelec/umelkyňa</p>'
    + '<h2 class="title">' + esc(u.meno) + '</h2>'
    + (u.popis ? '<div class="prose" style="margin-top:14px">' + u.popis + '</div>' : '')
    + '<h3 class="sec">Diela <span class="kod">' + diela.length + '</span></h3>'
    + (diela.length ? '<ul class="list">' + diela.map(function(d){
        var m = miestoById(d.miestoId);
        return '<li><button class="fitem" data-go-tab="diela" data-go-id="' + esc(d.id) + '">'
          + '<span class="fname">' + esc(d.nazov) + '</span>'
          + '<span class="fdir">' + (m ? esc(m.nazov) : "") + '</span>'
          + '</button></li>';
      }).join('') + '</ul>' : '<div class="empty">Zatiaľ žiadne priradené dielo.</div>')
    + '</div>';
  wireGo(detail);
}

/* ---------------------------------------------------------------------
   Vykresľovanie – PROGRAM
   --------------------------------------------------------------------- */
function renderProgram(){
  var groups = {}, order = [];
  DATA.program.forEach(function(p){
    var k = p.datum || "bez dátumu";
    if (!groups[k]){ groups[k] = []; order.push(k); }
    groups[k].push(p);
  });
  var html = crumb([{t:"program"}]) + '<div class="pad">'
    + '<p class="eyebrow">NOX:ART</p>'
    + '<h2 class="title">Program</h2>';
  if (!order.length){
    html += '<div class="empty" style="margin-top:14px">Program bude čoskoro doplnený.</div>';
  } else {
    order.forEach(function(k){
      html += '<div class="day-group"><h3 class="day-head">' + esc(formatDatum(k) || k) + '</h3>'
        + groups[k].map(function(p){
            var m = miestoById(p.miestoId);
            var cas = p.casOd ? esc(p.casOd) + (p.casDo ? "–" + esc(p.casDo) : "") : "";
            return '<div class="prog-item">'
              + '<div class="prog-time">' + cas + '</div>'
              + '<div class="prog-body">'
              + '<div class="prog-name">' + esc(p.nazov) + '</div>'
              + (m ? '<div class="prog-place"><a data-go-tab="mapa" data-go-id="' + esc(m.id) + '" href="javascript:void(0)">' + esc(m.nazov) + '</a></div>' : '')
              + (p.popis ? '<div class="prose" style="margin-top:6px">' + p.popis + '</div>' : '')
              + '</div></div>';
          }).join('')
        + '</div>';
    });
  }
  html += '</div>';
  panel.innerHTML = html;
  wireGo(panel);
}

/* ---------------------------------------------------------------------
   Hlavný render
   --------------------------------------------------------------------- */
function render(){
  var hasDetail = false;

  if (S.tab === "mapa"){
    renderMapaPanel();
    var m = S.id ? miestoById(S.id) : null;
    if (m){ renderMiestoDetail(m); hasDetail = true; } else { detail.innerHTML = ""; }
  } else if (S.tab === "diela"){
    renderDielaList();
    var d = S.id ? dieloById(S.id) : null;
    if (d){ renderDieloDetail(d); hasDetail = true; } else { detail.innerHTML = ""; }
  } else if (S.tab === "umelci"){
    renderUmelciList();
    var u = S.id ? umelecById(S.id) : null;
    if (u){ renderUmelecDetail(u); hasDetail = true; } else { detail.innerHTML = ""; }
  } else if (S.tab === "program"){
    renderProgram();
    detail.innerHTML = "";
  }

  appRoot.classList.toggle("has-detail", hasDetail);
  updateTabs();
  if (S.tab === "mapa" && S.id) focusMarker(S.id);
}

/* ---------------------------------------------------------------------
   Boot
   --------------------------------------------------------------------- */
parseHash();
wireTabs();
initMap();
render();

})();
