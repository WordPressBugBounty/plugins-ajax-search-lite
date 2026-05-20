/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// external "global"
var external_global_namespaceObject = Object(window.WPD)["global"];
;// ./src/client/plugin/core/actions/ga_events.ts
/* unused harmony import specifier */ var AslPlugin;


"use strict";
const ASL = window.ASL;
external_global_namespaceObject.AslPlugin.prototype.gaEvent = function(which, d) {
  let $this = this;
  if (typeof ASL.analytics == "undefined" || !ASL.analytics.method)
    return;
  let _gtag = typeof window.gtag == "function" ? window.gtag : false;
  if (_gtag === false && typeof window.dataLayer == "undefined")
    return;
  let tracking_id = $this.gaGetTrackingID();
  let def_data = {
    "search_id": $this.o.id,
    "search_name": $this.o.name,
    "phrase": $this.n("text").val(),
    "option_label": "",
    "option_value": "",
    "result_title": "",
    "result_url": "",
    "results_count": ""
  };
  const mergedData = { ...def_data, ...d };
  const items = ASL.analytics.event[which]?.items ?? [];
  for (const ev of items) {
    if (!ev.active) continue;
    const eventParams = { "send_to": "" };
    for (const p of ev.params ?? []) {
      if (!p.key) continue;
      let val = p.value;
      Object.keys(mergedData).forEach(function(k) {
        const v = String(mergedData[k]).replace(/[\s\n\r]+/g, " ").trim();
        val = val.replace(new RegExp("\\{" + k + "\\}", "gmi"), v);
      });
      eventParams[p.key] = val;
    }
    if (_gtag !== false) {
      if (tracking_id !== false) {
        tracking_id.forEach(function(id) {
          eventParams.send_to = id;
          _gtag("event", ev.action, eventParams);
        });
      } else {
        delete eventParams.send_to;
        _gtag("event", ev.action, eventParams);
      }
    } else if (window?.dataLayer?.push !== void 0) {
      window.dataLayer.push({
        "event": "gaEvent",
        "eventAction": ev.action,
        ...eventParams
      });
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.gaGetTrackingID = function() {
  if (typeof ASL.analytics == "undefined") {
    return false;
  }
  if (ASL.analytics.tracking_id) {
    return [ASL.analytics.tracking_id];
  }
  return false;
};
/* harmony default export */ var ga_events = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/ga.ts



Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;