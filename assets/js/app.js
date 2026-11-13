(function () {
	"use strict";

	function ready(fn) {
		if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
		else fn();
	}

	ready(function () {
		/* ---------- Header: menus ---------- */
		var userMenu = document.querySelector(".user-menu");

		function closeUserMenu() {
			if (!userMenu) return;
			userMenu.classList.remove("open");
			var t = userMenu.querySelector(".user-toggle");
			if (t) t.setAttribute("aria-expanded", "false");
		}

		document.querySelectorAll(".user-toggle").forEach(function (button) {
			button.addEventListener("click", function (e) {
				e.stopPropagation();
				var open = button.closest(".user-menu").classList.toggle("open");
				button.setAttribute("aria-expanded", open ? "true" : "false");
			});
		});

		document.addEventListener("click", function (e) {
			if (userMenu && !userMenu.contains(e.target)) closeUserMenu();
		});

		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") closeUserMenu();

			// "/" focuses search
			var tag = (e.target.tagName || "").toLowerCase();
			if (e.key === "/" && !e.ctrlKey && !e.metaKey && !e.altKey && tag !== "input" && tag !== "textarea" && tag !== "select" && !e.target.isContentEditable) {
				var input = document.querySelector(".header-search input") || document.querySelector(".search-form input");
				if (input) {
					e.preventDefault();
					input.focus();
				}
			}
		});

		/* ---------- Generic dropdowns ---------- */
		var dropdowns = Array.prototype.slice.call(document.querySelectorAll("[data-dropdown]"));

		function closeDropdown(dropdown) {
			dropdown.classList.remove("open");
			var toggle = dropdown.querySelector("[data-dropdown-toggle]");
			if (toggle) toggle.setAttribute("aria-expanded", "false");
			syncDropdownLock();
		}

		function closeDropdowns(except) {
			dropdowns.forEach(function (dropdown) {
				if (dropdown !== except) closeDropdown(dropdown);
			});
		}

		/* Lock page scroll while the mobile moderation sheet is open */
		function syncDropdownLock() {
			var sheetOpen = !!document.querySelector(".mod-dropdown.open");
			document.documentElement.classList.toggle("dropdown-sheet-open", sheetOpen);
		}

		dropdowns.forEach(function (dropdown) {
			var toggle = dropdown.querySelector("[data-dropdown-toggle]");
			if (!toggle) return;
			toggle.addEventListener("click", function () {
				var open = dropdown.classList.toggle("open");
				toggle.setAttribute("aria-expanded", open ? "true" : "false");
				if (open) closeDropdowns(dropdown);
				syncDropdownLock();
			});
			dropdown.querySelectorAll("[data-dropdown-close]").forEach(function (el) {
				el.addEventListener("click", function () {
					closeDropdown(dropdown);
				});
			});
		});

		document.addEventListener("click", function (e) {
			dropdowns.forEach(function (dropdown) {
				if (dropdown.classList.contains("open") && !dropdown.contains(e.target)) closeDropdown(dropdown);
			});
		});

		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") closeDropdowns();
		});

		/* ---------- Mobile sidebar ---------- */
		var sidebarToggle = document.querySelector("[data-sidebar-toggle]");
		var sidebar = document.getElementById("home-sidebar");

		function setSidebarOpen(open) {
			document.documentElement.classList.toggle("sidebar-open", open);
			if (!sidebarToggle) return;
			sidebarToggle.setAttribute("aria-expanded", open ? "true" : "false");
			sidebarToggle.setAttribute("aria-label", open ? "Close sidebar" : "Open sidebar");
		}

		if (sidebarToggle && sidebar) {
			document.documentElement.classList.add("has-sidebar");
			sidebarToggle.addEventListener("click", function () {
				setSidebarOpen(!document.documentElement.classList.contains("sidebar-open"));
			});
			document.addEventListener("keydown", function (e) {
				if (e.key === "Escape") setSidebarOpen(false);
			});
			window.addEventListener("resize", function () {
				if (window.innerWidth > 920) setSidebarOpen(false);
			});
		}

		/* ---------- Edit toggles ---------- */
		document.querySelectorAll(".edit-toggle").forEach(function (button) {
			button.addEventListener("click", function () {
				var target = document.getElementById(button.dataset.target);
				if (!target) return;
				var post = target.closest(".post");
				var opened = target.classList.toggle("open");
				if (post) post.classList.toggle("editing", opened);
				if (opened) {
					var ta = target.querySelector("textarea");
					if (ta) {
						ta.focus();
						ta.setSelectionRange(ta.value.length, ta.value.length);
					}
				}
			});
		});

		/* ---------- Confirm dialogs ---------- */
		document.querySelectorAll("[data-confirm]").forEach(function (el) {
			var message = el.dataset.confirm;
			if (el.tagName === "FORM") {
				el.addEventListener("submit", function (e) {
					if (!confirm(message)) e.preventDefault();
				});
			} else {
				el.addEventListener("click", function (e) {
					if (!confirm(message)) e.preventDefault();
				});
			}
		});

		/* ---------- Markdown editor ---------- */
		function wrap(ta, before, after, placeholder) {
			var s = ta.selectionStart,
				e = ta.selectionEnd;
			var sel = ta.value.slice(s, e) || placeholder;
			ta.setRangeText(before + sel + after, s, e, "end");
			ta.focus();
			ta.setSelectionRange(s + before.length, s + before.length + sel.length);
			ta.dispatchEvent(new Event("input", { bubbles: true }));
		}

		function prefixLines(ta, fn) {
			var s = ta.selectionStart,
				e = ta.selectionEnd,
				v = ta.value;
			var start = v.lastIndexOf("\n", s - 1) + 1;
			var endIdx = v.indexOf("\n", e);
			if (endIdx === -1) endIdx = v.length;
			var block = v.slice(start, endIdx).split("\n").map(fn).join("\n");
			ta.setRangeText(block, start, endIdx, "select");
			ta.focus();
			ta.dispatchEvent(new Event("input", { bubbles: true }));
		}

		var actions = {
			bold: function (ta) {
				wrap(ta, "**", "**", "bold text");
			},
			italic: function (ta) {
				wrap(ta, "*", "*", "italic text");
			},
			strike: function (ta) {
				wrap(ta, "~~", "~~", "text");
			},
			code: function (ta) {
				var sel = ta.value.slice(ta.selectionStart, ta.selectionEnd);
				if (sel.indexOf("\n") !== -1) wrap(ta, "```\n", "\n```", "code");
				else wrap(ta, "`", "`", "code");
			},
			link: function (ta) {
				var s = ta.selectionStart,
					e = ta.selectionEnd;
				var sel = ta.value.slice(s, e) || "link text";
				ta.setRangeText("[" + sel + "](https://)", s, e, "end");
				ta.focus();
				var urlStart = s + sel.length + 3;
				ta.setSelectionRange(urlStart, urlStart + 8);
			},
			quote: function (ta) {
				prefixLines(ta, function (l) {
					return "> " + l;
				});
			},
			ul: function (ta) {
				prefixLines(ta, function (l) {
					return "- " + l;
				});
			},
			ol: function (ta) {
				var i = 0;
				prefixLines(ta, function (l) {
					i++;
					return i + ". " + l;
				});
			},
		};

		document.querySelectorAll("[data-editor]").forEach(function (editor) {
			var ta = editor.querySelector("textarea");
			var counter = editor.querySelector("[data-count]");

			editor.querySelectorAll("[data-md]").forEach(function (btn) {
				btn.addEventListener("click", function () {
					var fn = actions[btn.dataset.md];
					if (fn && ta) fn(ta);
				});
			});

			if (counter && ta) {
				var max = parseInt(counter.dataset.max, 10) || 10000;
				var update = function () {
					var n = ta.value.length;
					counter.textContent = n + " / " + max;
					counter.classList.toggle("warn", n > max * 0.9);
				};
				ta.addEventListener("input", update);
				update();
			}

			if (ta) {
				ta.addEventListener("keydown", function (e) {
					if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
						var form = ta.closest("form");
						if (form) {
							e.preventDefault();
							if (form.requestSubmit) form.requestSubmit();
							else form.submit();
						}
					} else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "b") {
						e.preventDefault();
						actions.bold(ta);
					} else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "i") {
						e.preventDefault();
						actions.italic(ta);
					}
				});
			}
		});

		/* ---------- Quote reply ---------- */
		document.querySelectorAll(".quote-btn").forEach(function (btn) {
			btn.addEventListener("click", function () {
				var src = document.getElementById(btn.dataset.source);
				var ta = document.getElementById("reply-body");
				if (!src || !ta) return;

				var selection = window.getSelection ? String(window.getSelection()) : "";
				var text = (selection && src.contains(window.getSelection().anchorNode) ? selection : src.innerText).trim();
				if (text.length > 600) text = text.slice(0, 600).trim() + "…";

				var quoted =
					"> **" +
					btn.dataset.author +
					"** wrote:\n" +
					text
						.split("\n")
						.map(function (l) {
							return "> " + l;
						})
						.join("\n") +
					"\n\n";

				ta.value = (ta.value ? ta.value.replace(/\s*$/, "\n\n") : "") + quoted;
				ta.dispatchEvent(new Event("input", { bubbles: true }));
				ta.scrollIntoView({ behavior: "smooth", block: "center" });
				ta.focus();
				ta.setSelectionRange(ta.value.length, ta.value.length);
			});
		});

		/* ---------- Code blocks: highlight + copy ---------- */
		var KEYWORDS = new Set("abstract as async await break case catch class const continue default def del do echo elif else elseif enum except export extends false final finally fn for foreach from function global if implements import in include interface is lambda let match namespace new nil none not null or pass print private protected public raise require return self static struct super switch this throw trait true try type typeof use var void while with yield and select insert update delete where join group order by limit create table alter drop into values set on null".split(" "));

		var HASH_COMMENT = new Set(["", "py", "python", "sh", "bash", "shell", "zsh", "yaml", "yml", "rb", "ruby", "php", "pl", "toml", "ini", "conf", "dockerfile", "makefile", "r"]);
		var DASH_COMMENT = new Set(["sql", "lua", "hs", "haskell"]);

		function highlight(code, lang) {
			var parts = ["(\\/\\*[\\s\\S]*?\\*\\/|\\/\\/[^\\n]*"];
			if (HASH_COMMENT.has(lang)) parts[0] += "|#[^\\n]*";
			if (DASH_COMMENT.has(lang)) parts[0] += "|--[^\\n]*";
			parts[0] += ")";
			parts.push("(\"(?:[^\"\\\\\\n]|\\\\.)*\"|'(?:[^'\\\\\\n]|\\\\.)*'|`(?:[^`\\\\]|\\\\.)*`)");
			parts.push("(\\$[A-Za-z_]\\w*|@[A-Za-z_]\\w*)");
			parts.push("(\\b\\d[\\d_]*(?:\\.\\d+)?\\b)");
			parts.push("(\\b[A-Za-z_]\\w*\\b)");
			var re = new RegExp(parts.join("|"), "g");

			var frag = document.createDocumentFragment();
			var last = 0,
				m;
			var add = function (text, cls) {
				if (!text) return;
				if (!cls) {
					frag.appendChild(document.createTextNode(text));
					return;
				}
				var s = document.createElement("span");
				s.className = cls;
				s.textContent = text;
				frag.appendChild(s);
			};

			while ((m = re.exec(code)) !== null) {
				add(code.slice(last, m.index));
				if (m[1]) add(m[0], "tok-c");
				else if (m[2]) add(m[0], "tok-s");
				else if (m[3]) add(m[0], "tok-v");
				else if (m[4]) add(m[0], "tok-n");
				else if (KEYWORDS.has(m[0].toLowerCase())) add(m[0], "tok-k");
				else add(m[0]);
				last = re.lastIndex;
			}
			add(code.slice(last));
			return frag;
		}

		document.querySelectorAll(".post-body pre").forEach(function (pre) {
			var codeEl = pre.querySelector("code");
			if (!codeEl) return;
			var raw = codeEl.textContent;

			if (raw.length < 20000) {
				var frag = highlight(raw, (pre.dataset.lang || "").toLowerCase());
				codeEl.textContent = "";
				codeEl.appendChild(frag);
			}

			var btn = document.createElement("button");
			btn.type = "button";
			btn.className = "code-copy";
			btn.textContent = "Copy";
			btn.addEventListener("click", function () {
				var done = function () {
					btn.textContent = "Copied";
					setTimeout(function () {
						btn.textContent = "Copy";
					}, 1500);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(raw).then(done, function () {});
				}
			});
			pre.appendChild(btn);
		});

		/* ---------- Announcement banner: dismiss (remembered per message) ---------- */
		document.querySelectorAll("[data-announcement]").forEach(function (banner) {
			var key = "announcement-dismissed-" + banner.dataset.announcement;
			try {
				if (localStorage.getItem(key) === "1") banner.hidden = true;
			} catch (err) {}
			var close = banner.querySelector(".announcement-close");
			if (close) {
				close.addEventListener("click", function () {
					banner.hidden = true;
					try {
						localStorage.setItem(key, "1");
					} catch (err) {}
				});
			}
		});

		/* ---------- Home widget editor (reorder + inherit toggle) ---------- */
		document.querySelectorAll("[data-widget-editor]").forEach(function (editor) {
			var list = editor.querySelector(".widget-rows");
			var inherit = editor.querySelector("[data-widget-inherit]");

			editor.addEventListener("click", function (e) {
				var btn = e.target.closest("[data-move]");
				if (!btn || editor.classList.contains("is-inherit")) return;
				var row = btn.closest("[data-widget-row]");
				if (btn.dataset.move === "up" && row.previousElementSibling) {
					list.insertBefore(row, row.previousElementSibling);
				} else if (btn.dataset.move === "down" && row.nextElementSibling) {
					list.insertBefore(row.nextElementSibling, row);
				}
				btn.focus();
			});

			if (inherit) {
				inherit.addEventListener("change", function () {
					editor.classList.toggle("is-inherit", inherit.checked);
				});
			}
		});

		/* ---------- Custom select (native <select> -> styled listbox) ---------- */
		var openSelect = null;
		var selectUid = 0;

		function closeCustomSelect(instance, focusTrigger) {
			if (!instance) return;
			instance.wrap.classList.remove("open", "drop-up");
			instance.trigger.setAttribute("aria-expanded", "false");
			document.documentElement.classList.remove("select-sheet-open");
			if (focusTrigger) instance.trigger.focus();
			if (openSelect === instance) openSelect = null;
		}

		function enhanceSelect(select) {
			var wrap = select.closest(".select-wrap");
			if (!wrap || wrap.classList.contains("is-enhanced")) return;

			var uid = ++selectUid;
			var listId = "cs-list-" + uid;
			var label = select.getAttribute("aria-label");
			var labelEl = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;

			wrap.classList.add("is-enhanced");
			select.classList.add("select-native");
			select.tabIndex = -1;
			select.setAttribute("aria-hidden", "true");

			var oldIcon = wrap.querySelector("i[data-lucide], svg");
			if (oldIcon) oldIcon.remove();

			var trigger = document.createElement("button");
			trigger.type = "button";
			trigger.className = "select-trigger";
			trigger.setAttribute("aria-haspopup", "listbox");
			trigger.setAttribute("aria-expanded", "false");
			trigger.setAttribute("aria-controls", listId);
			if (label) trigger.setAttribute("aria-label", label);
			if (labelEl) {
				if (!labelEl.id) labelEl.id = "cs-label-" + uid;
				trigger.setAttribute("aria-labelledby", labelEl.id);
			}
			if (select.disabled) trigger.disabled = true;

			var valueEl = document.createElement("span");
			valueEl.className = "select-value";
			trigger.appendChild(valueEl);
			var caret = document.createElement("i");
			caret.setAttribute("data-lucide", "chevron-down");
			caret.className = "select-caret";
			trigger.appendChild(caret);

			var backdrop = document.createElement("div");
			backdrop.className = "select-backdrop";

			var panel = document.createElement("div");
			panel.className = "select-panel";

			var sheetTitle = document.createElement("div");
			sheetTitle.className = "select-sheet-title";
			sheetTitle.textContent = label || (labelEl ? labelEl.textContent : "");
			panel.appendChild(sheetTitle);

			var list = document.createElement("ul");
			list.className = "select-list";
			list.id = listId;
			list.setAttribute("role", "listbox");
			list.tabIndex = -1;
			if (label) list.setAttribute("aria-label", label);
			panel.appendChild(list);

			var items = [];
			Array.prototype.forEach.call(select.options, function (opt, i) {
				var li = document.createElement("li");
				li.className = "select-option";
				li.setAttribute("role", "option");
				li.id = listId + "-" + i;
				li.dataset.index = i;
				if (opt.disabled) li.setAttribute("aria-disabled", "true");
				var text = document.createElement("span");
				text.className = "select-option-text";
				text.textContent = opt.textContent;
				var check = document.createElement("i");
				check.setAttribute("data-lucide", "check");
				check.className = "select-check";
				li.appendChild(text);
				li.appendChild(check);
				list.appendChild(li);
				items.push(li);
			});

			wrap.appendChild(trigger);
			wrap.appendChild(backdrop);
			wrap.appendChild(panel);

			var instance = { wrap: wrap, trigger: trigger };
			var active = -1;
			var typed = "";
			var typedTimer = null;

			function sync() {
				var idx = select.selectedIndex;
				valueEl.textContent = idx >= 0 ? select.options[idx].textContent : "";
				items.forEach(function (li, i) {
					li.setAttribute("aria-selected", i === idx ? "true" : "false");
				});
			}

			function setActive(i, scroll) {
				if (i < 0 || i >= items.length) return;
				if (active >= 0 && items[active]) items[active].classList.remove("active");
				active = i;
				items[i].classList.add("active");
				list.setAttribute("aria-activedescendant", items[i].id);
				if (scroll !== false) {
					var top = items[i].offsetTop;
					var bottom = top + items[i].offsetHeight;
					if (top < list.scrollTop) list.scrollTop = top - 4;
					else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight + 4;
				}
			}

			function nextEnabled(from, dir) {
				var i = from + dir;
				while (i >= 0 && i < items.length) {
					if (!select.options[i].disabled) return i;
					i += dir;
				}
				return from;
			}

			function choose(i) {
				if (i < 0 || i >= items.length || select.options[i].disabled) return;
				var changed = select.selectedIndex !== i;
				select.selectedIndex = i;
				sync();
				closeCustomSelect(instance, true);
				if (changed) select.dispatchEvent(new Event("change", { bubbles: true }));
			}

			function open() {
				if (select.disabled) return;
				if (openSelect && openSelect !== instance) closeCustomSelect(openSelect);
				wrap.classList.remove("drop-up");
				wrap.classList.add("open");
				trigger.setAttribute("aria-expanded", "true");
				openSelect = instance;

				var isSheet = window.matchMedia("(max-width: 640px)").matches;
				if (isSheet) {
					document.documentElement.classList.add("select-sheet-open");
				} else {
					var rect = trigger.getBoundingClientRect();
					var need = Math.min(panel.offsetHeight, 280) + 12;
					var below = window.innerHeight - rect.bottom;
					if (below < need && rect.top > below) wrap.classList.add("drop-up");
				}
				setActive(select.selectedIndex >= 0 ? select.selectedIndex : 0);
				list.focus({ preventScroll: true });
			}

			instance.open = open;

			trigger.addEventListener("click", function () {
				if (wrap.classList.contains("open")) closeCustomSelect(instance, true);
				else open();
			});

			trigger.addEventListener("keydown", function (e) {
				if (e.key === "ArrowDown" || e.key === "ArrowUp") {
					e.preventDefault();
					open();
				}
			});

			backdrop.addEventListener("click", function () {
				closeCustomSelect(instance, true);
			});

			list.addEventListener("click", function (e) {
				var li = e.target.closest(".select-option");
				if (li) choose(parseInt(li.dataset.index, 10));
			});

			list.addEventListener("mousemove", function (e) {
				var li = e.target.closest(".select-option");
				if (li && !li.classList.contains("active")) setActive(parseInt(li.dataset.index, 10), false);
			});

			list.addEventListener("keydown", function (e) {
				var k = e.key;
				if (k === "ArrowDown") {
					e.preventDefault();
					setActive(nextEnabled(active, 1));
				} else if (k === "ArrowUp") {
					e.preventDefault();
					setActive(nextEnabled(active, -1));
				} else if (k === "Home") {
					e.preventDefault();
					setActive(nextEnabled(-1, 1));
				} else if (k === "End") {
					e.preventDefault();
					setActive(nextEnabled(items.length, -1));
				} else if (k === "Enter" || k === " ") {
					e.preventDefault();
					choose(active);
				} else if (k === "Escape") {
					e.preventDefault();
					e.stopPropagation();
					closeCustomSelect(instance, true);
				} else if (k === "Tab") {
					closeCustomSelect(instance);
				} else if (k.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
					e.stopPropagation();
					typed += k.toLowerCase();
					clearTimeout(typedTimer);
					typedTimer = setTimeout(function () {
						typed = "";
					}, 600);
					for (var i = 0; i < items.length; i++) {
						var idx = (active + 1 + i) % items.length;
						if (!select.options[idx].disabled && select.options[idx].textContent.trim().toLowerCase().indexOf(typed) === 0) {
							setActive(idx);
							break;
						}
					}
				}
			});

			select.addEventListener("change", sync);
			var form = select.form;
			if (form) form.addEventListener("reset", function () { setTimeout(sync, 0); });

			sync();
		}

		document.querySelectorAll(".select-wrap select").forEach(enhanceSelect);

		document.addEventListener("click", function (e) {
			if (openSelect && !openSelect.wrap.contains(e.target)) closeCustomSelect(openSelect);
		});
		window.addEventListener("resize", function () {
			if (openSelect) closeCustomSelect(openSelect);
		});

		/* ---------- Navigate on select change (CSP-safe) ---------- */
		document.querySelectorAll("select[data-nav-select]").forEach(function (select) {
			select.addEventListener("change", function () {
				if (select.value) window.location.href = select.value;
			});
		});

		/* ---------- Icons (en sonda: DOM değişiklikleri tamamlandı) ---------- */
		if (window.lucide) window.lucide.createIcons();
	});
})();
