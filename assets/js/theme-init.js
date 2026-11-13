/* Runs before first paint: applies the device-level theme override (set via the header button). */
(function () {
	try {
		var t = localStorage.getItem("forum-theme");
		if (t === "light" || t === "dark" || t === "system") document.documentElement.setAttribute("data-theme", t);
	} catch (e) {}
})();
