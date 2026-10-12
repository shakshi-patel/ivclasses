/* IVClasses Marketing Site — shared interactions */
(function () {
    "use strict";

    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    /* Year */
    document.querySelectorAll("[data-year]").forEach((el) => {
        el.textContent = String(new Date().getFullYear());
    });

    /* Navbar scroll */
    const navbar = document.getElementById("navbar");
    const onScrollNav = () => {
        if (!navbar) return;
        navbar.classList.toggle("scrolled", window.scrollY > 40);
    };
    onScrollNav();
    window.addEventListener("scroll", onScrollNav, { passive: true });

    /* Features dropdown (desktop) */
    const navItem = document.querySelector(".nav-item.has-drop");
    const dropToggle = document.querySelector(".drop-toggle");
    if (navItem && dropToggle) {
        dropToggle.addEventListener("click", (e) => {
            e.preventDefault();
            navItem.classList.toggle("open");
        });
        document.addEventListener("click", (e) => {
            if (!navItem.contains(e.target)) navItem.classList.remove("open");
        });
    }

    /* Mobile drawer */
    const toggle = document.getElementById("navToggle");
    const drawer = document.getElementById("mobileDrawer");

    function closeDrawer() {
        if (!drawer || !toggle) return;
        drawer.hidden = true;
        toggle.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
    }

    function openDrawer() {
        if (!drawer || !toggle) return;
        drawer.hidden = false;
        toggle.classList.add("open");
        toggle.setAttribute("aria-expanded", "true");
    }

    if (toggle && drawer) {
        toggle.addEventListener("click", () => {
            if (drawer.hidden) openDrawer();
            else closeDrawer();
        });
        drawer.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", closeDrawer);
        });
    }

    /* Scroll reveal */
    const reveals = document.querySelectorAll("[data-reveal]");
    if (reduceMotion) {
        reveals.forEach((el) => el.classList.add("is-visible"));
    } else if ("IntersectionObserver" in window) {
        const obs = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    const delay = Number(el.getAttribute("data-delay") || 0);
                    window.setTimeout(
                        () => el.classList.add("is-visible"),
                        delay,
                    );
                    observer.unobserve(el);
                });
            },
            { threshold: 0.12, rootMargin: "0px 0px -40px 0px" },
        );
        reveals.forEach((el) => obs.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add("is-visible"));
    }

    /* How-it-works line */
    const steps = document.getElementById("steps");
    if (steps) {
        if ("IntersectionObserver" in window) {
            const sObs = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        steps.classList.add("is-drawn");
                        observer.unobserve(steps);
                    });
                },
                { threshold: 0.35 },
            );
            sObs.observe(steps);
        } else {
            steps.classList.add("is-drawn");
        }
    }

    /* Web CRM sticky module nav spy */
    const modules = document.querySelectorAll(".feature-module[id]");
    const moduleLinks = document.querySelectorAll(
        ".module-nav a, .mobile-module-pills a",
    );
    if (
        modules.length &&
        moduleLinks.length &&
        "IntersectionObserver" in window
    ) {
        const map = new Map();
        modules.forEach((m) => map.set(m.id, m));

        const setActive = (id) => {
            moduleLinks.forEach((a) => {
                a.classList.toggle(
                    "active",
                    a.getAttribute("href") === "#" + id,
                );
            });
        };

        const mObs = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) setActive(entry.target.id);
                });
            },
            { rootMargin: "-30% 0px -55% 0px", threshold: 0 },
        );
        modules.forEach((m) => mObs.observe(m));
    }

    /* Demo form */
    const form = document.getElementById("demoForm");
    const success = document.getElementById("formSuccess");

    function clearErrors() {
        if (!form) return;
        form.querySelectorAll(".field").forEach((f) =>
            f.classList.remove("has-error"),
        );
        form.querySelectorAll(".error").forEach((e) => {
            e.textContent = "";
        });
    }

    function setError(name, message) {
        if (!form) return;
        const err = form.querySelector('[data-error="' + name + '"]');
        if (err) err.textContent = message;
        const input = form.elements.namedItem(name);
        if (!input || !("closest" in input)) return;
        const field = input.closest(".field");
        if (field) field.classList.add("has-error");
    }

    function isValidPhone(v) {
        return v.replace(/\D/g, "").length >= 10;
    }

    function isValidEmail(v) {
        if (!v) return true;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    if (form) {
        let submitting = false;
        const submitBtn = form.querySelector('button[type="submit"]');
        const demoLoader = document.getElementById("demoLoader");

        function showDemoLoader() {
            if (!demoLoader) return;
            demoLoader.hidden = false;
            demoLoader.setAttribute("aria-busy", "true");
        }

        function hideDemoLoader() {
            if (!demoLoader) return;
            demoLoader.hidden = true;
            demoLoader.setAttribute("aria-busy", "false");
        }

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            clearErrors();

            if (submitting) {
                return;
            }

            const honeypot = form.elements.namedItem("website");
            if (honeypot && "value" in honeypot && honeypot.value) return;

            const fullName = (form.elements.namedItem("contact_person_name")?.value || "").trim();
            const institute = (form.elements.namedItem("institute_name")?.value || "").trim();
            const city = (form.elements.namedItem("city")?.value || "").trim();
            const phone = (form.elements.namedItem("contact_no")?.value || "").trim();
            const email = (form.elements.namedItem("email")?.value || "").trim();
            const demoDateValue = (form.elements.namedItem("demo_date")?.value || "").trim();
            const demoTimeValue = (form.elements.namedItem("demo_start_time")?.value || "").trim();

            let ok = true;
            if (!fullName) {
                setError("contact_person_name", "Full name is required");
                ok = false;
            }
            if (!institute) {
                setError("institute_name", "Institute name is required");
                ok = false;
            }
            if (!city) {
                setError("city", "City is required");
                ok = false;
            }
            if (!phone) {
                setError("contact_no", "Phone / WhatsApp is required");
                ok = false;
            } else if (!isValidPhone(phone)) {
                setError("contact_no", "Enter a valid phone number");
                ok = false;
            }
            if (!email) {
                setError("email", "Email is required");
                ok = false;
            } else if (!isValidEmail(email)) {
                setError("email", "Enter a valid email");
                ok = false;
            }
            if (!demoDateValue) {
                setError("demo_date", "Demo date is required");
                ok = false;
            }
            if (!demoTimeValue) {
                setError("demo_start_time", "Please select an available time");
                ok = false;
            } else {
                const timeField = form.elements.namedItem("demo_start_time");
                const selected = timeField && timeField.options ? timeField.options[timeField.selectedIndex] : null;
                if (selected && selected.dataset && selected.dataset.booked === "1") {
                    setError("demo_start_time", "This time is already booked for a demo.");
                    ok = false;
                }
            }
            if (!ok) return;

            const timeSelect = form.elements.namedItem("demo_start_time");
            if (timeSelect && "disabled" in timeSelect) {
                timeSelect.disabled = false;
            }

            const formData = new FormData(form);
            submitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            showDemoLoader();

            fetch(form.action, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: formData,
            })
                .then(async (response) => {
                    const contentType = response.headers.get("content-type") || "";
                    const isJson = contentType.includes("application/json");
                    const payload = isJson ? await response.json() : null;

                    if (!isJson) {
                        throw new Error("Something went wrong. Please try again.");
                    }

                    if (!response.ok) {
                        if (payload && payload.errors) {
                            Object.keys(payload.errors).forEach((field) => {
                                const messages = payload.errors[field];
                                if (messages && messages[0]) {
                                    setError(field, messages[0]);
                                }
                            });
                        }
                        throw new Error(
                            (payload && payload.message) ||
                                "Something went wrong. Please try again.",
                        );
                    }

                    return payload;
                })
                .then((result) => {
                    const redirectUrl =
                        (result && result.redirect) ||
                        form.getAttribute("data-success-url");
                    if (redirectUrl) {
                        window.location.replace(redirectUrl);
                        return;
                    }

                    hideDemoLoader();
                    form.hidden = true;
                    if (success) success.hidden = false;
                })
                .catch((err) => {
                    submitting = false;
                    hideDemoLoader();
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    alert(
                        err.message ||
                            "Something went wrong. Please try again.",
                    );
                });
        });
    }

    /* FAQ accordion */
    const faqList = document.getElementById("faqList");
    if (faqList) {
        faqList.querySelectorAll(".faq-q").forEach((btn) => {
            btn.addEventListener("click", () => {
                const item = btn.closest(".faq-item");
                if (!item) return;
                const open = item.classList.contains("is-open");
                faqList.querySelectorAll(".faq-item").forEach((el) => {
                    el.classList.remove("is-open");
                    const q = el.querySelector(".faq-q");
                    if (q) q.setAttribute("aria-expanded", "false");
                });
                if (!open) {
                    item.classList.add("is-open");
                    btn.setAttribute("aria-expanded", "true");
                }
            });
        });
    }

    /* Features cards: show 2 bullets + Read more (same layout on every card) */
    document.querySelectorAll(".feature-card .check-list").forEach((list) => {
        if (list.classList.contains("check-list-more")) return;
        if (list.dataset.collapseInit === "1") return;
        list.dataset.collapseInit = "1";

        const items = Array.from(list.children).filter(
            (el) => el.tagName === "LI",
        );
        if (!items.length) return;

        const more = document.createElement("ul");
        more.className = "check-list check-list-more";
        more.hidden = true;

        items.slice(2).forEach((li) => more.appendChild(li));
        const extraCount = more.children.length;

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "read-more-btn";
        btn.setAttribute("aria-expanded", "false");

        if (extraCount > 0) {
            btn.textContent = "Read more";
            btn.addEventListener("click", () => {
                const open = more.hidden;
                more.hidden = !open;
                btn.setAttribute("aria-expanded", open ? "true" : "false");
                btn.textContent = open ? "Show less" : "Read more";
            });
        } else {
            btn.textContent = "Read more";
            btn.classList.add("is-empty");
            btn.disabled = true;
            btn.setAttribute("aria-disabled", "true");
        }

        list.after(more);
        more.after(btn);
    });

    /* Hero soft parallax */
    if (!reduceMotion) {
        const heroVisuals = document.getElementById("heroVisuals");
        if (heroVisuals && window.matchMedia("(min-width: 1024px)").matches) {
            const items = heroVisuals.querySelectorAll("[data-parallax]");
            let ticking = false;
            heroVisuals.addEventListener("mousemove", (e) => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(() => {
                    const rect = heroVisuals.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;
                    items.forEach((el) => {
                        const depth = Number(
                            el.getAttribute("data-parallax") || 0,
                        );
                        el.style.setProperty("--px", x * depth * 100 + "px");
                        el.style.setProperty("--py", y * depth * 100 + "px");
                    });
                    ticking = false;
                });
            });
            heroVisuals.addEventListener("mouseleave", () => {
                items.forEach((el) => {
                    el.style.setProperty("--px", "0px");
                    el.style.setProperty("--py", "0px");
                });
            });
        }
    }
})();
