/* Runs before first paint: restores the visitor's theme / accent / radius choices. */
(function () {
	var d = document.documentElement;
	d.classList.add("js");
	try {
		var t = localStorage.getItem("han-docs-theme");
		if (t === "light" || t === "dark") d.setAttribute("data-theme", t);
		var a = localStorage.getItem("han-docs-accent");
		if (a && a !== "zinc") d.setAttribute("data-accent", a);
		var r = localStorage.getItem("han-docs-radius");
		if (r === "sharp" || r === "round") d.setAttribute("data-radius", r);
	} catch (e) {}
})();
