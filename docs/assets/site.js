(function () {
	var root = document.documentElement;
	function save(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

	/* Theme toggle */
	var toggle = document.querySelector(".theme-toggle");
	if (toggle) toggle.addEventListener("click", function () {
		var dark = root.getAttribute("data-theme") === "dark" ||
			(!root.getAttribute("data-theme") && matchMedia("(prefers-color-scheme: dark)").matches);
		var next = dark ? "light" : "dark";
		root.setAttribute("data-theme", next);
		save("han-docs-theme", next);
	});

	/* Live customizer: accent + corners, same presets as the app */
	function press(group, btn) {
		group.forEach(function (b) { b.setAttribute("aria-pressed", String(b === btn)); });
	}
	var swatches = [].slice.call(document.querySelectorAll(".swatch"));
	var current = root.getAttribute("data-accent") || "zinc";
	swatches.forEach(function (s) {
		s.setAttribute("aria-pressed", String(s.dataset.accent === current));
		s.addEventListener("click", function () {
			var a = s.dataset.accent;
			if (a === "zinc") root.removeAttribute("data-accent"); else root.setAttribute("data-accent", a);
			save("han-docs-accent", a);
			press(swatches, s);
		});
	});
	var radii = [].slice.call(document.querySelectorAll("[data-radius-btn]"));
	var curR = root.getAttribute("data-radius") || "default";
	radii.forEach(function (b) {
		b.setAttribute("aria-pressed", String(b.dataset.radiusBtn === curR));
		b.addEventListener("click", function () {
			var r = b.dataset.radiusBtn;
			if (r === "default") root.removeAttribute("data-radius"); else root.setAttribute("data-radius", r);
			save("han-docs-radius", r);
			press(radii, b);
		});
	});

	/* Demo: thread filter tabs */
	var tabs = [].slice.call(document.querySelectorAll(".mock-tab"));
	var rows = [].slice.call(document.querySelectorAll(".mock-row[data-tags]"));
	tabs.forEach(function (t) {
		t.addEventListener("click", function () {
			press(tabs, t);
			rows.forEach(function (r) {
				r.hidden = r.dataset.tags.split(" ").indexOf(t.dataset.filter) === -1;
				r.style.borderBottom = "";
			});
			var shown = rows.filter(function (r) { return !r.hidden; });
			if (shown.length) shown[shown.length - 1].style.borderBottom = "0";
		});
	});

	/* Demo: admin switches */
	[].forEach.call(document.querySelectorAll(".toggle-row"), function (b) {
		b.addEventListener("click", function () {
			b.setAttribute("aria-checked", String(b.getAttribute("aria-checked") !== "true"));
		});
	});

	/* Copy buttons */
	[].forEach.call(document.querySelectorAll(".copy-btn"), function (btn) {
		btn.addEventListener("click", function () {
			var el = document.getElementById(btn.dataset.copy);
			if (!el || !navigator.clipboard) return;
			navigator.clipboard.writeText(el.innerText).then(function () {
				btn.classList.add("done");
				btn.querySelector("span").textContent = "Copied";
				setTimeout(function () { btn.classList.remove("done"); btn.querySelector("span").textContent = "Copy"; }, 1800);
			});
		});
	});

	/* Scroll reveal */
	var items = [].slice.call(document.querySelectorAll(".reveal"));
	if (!("IntersectionObserver" in window)) { items.forEach(function (i) { i.classList.add("in"); }); return; }
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } });
	}, { threshold: 0.12, rootMargin: "0px 0px -40px" });
	items.forEach(function (i) { io.observe(i); });
})();
