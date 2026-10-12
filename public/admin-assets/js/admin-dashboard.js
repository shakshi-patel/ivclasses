/**
 * IV Classes — dashboard charts.
 * Fetches JSON from data-url and renders ApexCharts. Colours from iv-tokens.css.
 */
(function () {
    'use strict';

    var css = getComputedStyle(document.documentElement);
    var token = function (name) {
        return css.getPropertyValue(name).trim() || '';
    };

    var C = {
        primary: token('--iv-primary') || '#0f5c4d',
        primaryDark: token('--iv-primary-dark') || '#08483d',
        gold: token('--iv-secondary') || '#d9a441',
        goldDark: token('--iv-secondary-dark') || '#b7862a',
        info: token('--iv-info') || '#3b82a0',
        success: token('--iv-success') || '#2e8b68',
        error: token('--iv-error') || '#d9534f',
        text: token('--iv-text-secondary') || '#667085',
        border: 'rgba(' + (token('--iv-text-rgb') || '31, 41, 51') + ', 0.08)',
    };

    var money = function (v) {
        var n = Number(v) || 0;
        try {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency', currency: 'INR', maximumFractionDigits: 0,
            }).format(n);
        } catch (e) {
            return '₹' + Math.round(n).toLocaleString('en-IN');
        }
    };

    var moneyShort = function (v) {
        var n = Number(v) || 0;
        try {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency', currency: 'INR', notation: 'compact', maximumFractionDigits: 1,
            }).format(n);
        } catch (e) {
            return money(n);
        }
    };

    var nums = function (arr) {
        return (Array.isArray(arr) ? arr : []).map(function (v) {
            var n = Number(v);
            return Number.isFinite(n) ? n : 0;
        });
    };

    var seriesSum = function (series) {
        if (!Array.isArray(series) || !series.length) {
            return 0;
        }
        return series.reduce(function (total, s) {
            var values = Array.isArray(s) ? s : (s && Array.isArray(s.data) ? s.data : []);
            return total + nums(values).reduce(function (a, b) { return a + b; }, 0);
        }, 0);
    };

    var setText = function (key, value) {
        document.querySelectorAll('[data-ivd-total="' + key + '"]').forEach(function (el) {
            el.textContent = value;
        });
    };

    var base = function (height) {
        return {
            chart: {
                height: height,
                parentHeightOffset: 0,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: false },
                zoom: { enabled: false },
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: C.border,
                strokeDashArray: 4,
                padding: { top: 0, left: 8, right: 8, bottom: 0 },
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                labels: { colors: C.text },
            },
            xaxis: {
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: C.text, fontSize: '11px' } },
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { style: { colors: C.text, fontSize: '11px' } },
            },
            tooltip: { theme: 'light' },
        };
    };

    var builders = {
        revenue: function (d, h) {
            setText('revenue', money(d.total || 0));
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: [{ name: 'Revenue', data: nums((d.series && d.series[0] && d.series[0].data) || d.series) }],
                colors: [C.primary],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '42%' } },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                yaxis: Object.assign(base(h).yaxis, {
                    labels: { style: { colors: C.text, fontSize: '11px' }, formatter: moneyShort },
                }),
                tooltip: { y: { formatter: money } },
                legend: { show: false },
            });
        },

        institutes: function (d, h) {
            var series = (d.series || []).map(function (s) {
                return { name: s.name, type: s.type || 'line', data: nums(s.data) };
            });
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'line' }),
                series: series,
                colors: [C.gold, C.primary],
                stroke: { width: [0, 3], curve: 'smooth' },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '38%' } },
                markers: { size: [0, 4], strokeWidth: 2, strokeColors: '#fff' },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                legend: Object.assign(base(h).legend, { show: true }),
            });
        },

        plans: function (d, h, totalLabel) {
            var noun = totalLabel ? totalLabel.toLowerCase().replace(/s$/, '') : 'institute';
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'donut' }),
                series: nums(d.series),
                labels: d.labels || [],
                colors: [C.primary, C.gold, C.info, C.success, C.primaryDark, C.goldDark, C.error],
                stroke: { width: 3, colors: ['#fff'] },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '12px',
                    labels: { colors: C.text },
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '72%',
                            labels: {
                                show: true,
                                name: { color: C.text, fontSize: '12px', offsetY: 18 },
                                value: { color: C.primaryDark, fontSize: '1.6rem', fontWeight: 700, offsetY: -14 },
                                total: {
                                    show: true,
                                    label: totalLabel || 'Institutes',
                                    color: C.text,
                                    fontSize: '12px',
                                    formatter: function () { return d.total; },
                                },
                            },
                        },
                    },
                },
                tooltip: {
                    y: {
                        formatter: function (v) {
                            return v + ' ' + noun + (v === 1 ? '' : 's');
                        },
                    },
                },
            });
        },

        inquiries: function (d, h) {
            setText('conversion', (d.conversion_rate || 0) + '%');
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: [{ name: 'Leads', data: nums((d.series && d.series[0] && d.series[0].data) || d.series) }],
                colors: [C.primary],
                plotOptions: {
                    bar: { horizontal: true, borderRadius: 6, barHeight: '62%', dataLabels: { position: 'top' } },
                },
                dataLabels: {
                    enabled: true,
                    offsetX: 22,
                    style: { fontSize: '12px', fontWeight: 700, colors: [C.primaryDark] },
                },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [], labels: { show: false } }),
                yaxis: { labels: { style: { colors: C.text, fontSize: '12px', fontWeight: 600 } } },
                legend: { show: false },
            });
        },

        logins: function (d, h) {
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'area' }),
                series: (d.series || []).map(function (s) {
                    return { name: s.name, data: nums(s.data) };
                }),
                colors: [C.primary, C.error],
                stroke: { width: 2.5, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 95, 100] },
                },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [], tickAmount: 7 }),
                legend: Object.assign(base(h).legend, { show: true }),
            });
        },

        fees_trend: function (d, h) {
            setText('fees_total', money(d.total || 0));
            var rows = Array.isArray(d.series) ? d.series : [];
            var hasExpenses = rows.length > 1;
            var series = rows.map(function (row, i) {
                return {
                    name: row.name || (i === 0 ? 'Fee collection' : 'Expenses'),
                    data: nums(row.data),
                };
            });

            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: series,
                colors: [C.primary, C.error],
                plotOptions: { bar: { borderRadius: 6, columnWidth: hasExpenses ? '55%' : '42%' } },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                yaxis: Object.assign(base(h).yaxis, {
                    labels: { style: { colors: C.text, fontSize: '11px' }, formatter: moneyShort },
                }),
                legend: Object.assign(base(h).legend, { show: hasExpenses }),
                tooltip: { shared: true, intersect: false, y: { formatter: money } },
            });
        },

        attendance_trend: function (d, h) {
            setText('attendance_rate', (d.rate || 0) + '%');
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar', stacked: true }),
                series: (d.series || []).map(function (row) {
                    return { name: row.name, data: nums(row.data) };
                }),
                colors: [C.success, C.gold, C.error],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '48%' } },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                legend: Object.assign(base(h).legend, { show: true }),
                tooltip: { shared: true, intersect: false },
            });
        },

        admissions: function (d, h) {
            setText('admissions_total', d.total || 0);
            var data = nums((d.series && d.series[0] && d.series[0].data) || []);
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'area' }),
                series: [{ name: 'New admissions', data: data }],
                colors: [C.goldDark],
                stroke: { width: 3, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 95, 100] },
                },
                markers: { size: 4, strokeColors: '#fff', strokeWidth: 2, colors: [C.gold] },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                legend: { show: false },
            });
        },

        lead_sources: function (d, h) {
            var s = d.statuses || {};
            setText('lead_new', s.new || 0);
            setText('lead_follow_up', s.follow_up || 0);
            setText('lead_converted', s.converted || 0);
            return builders.plans(Object.assign({}, d, { series: nums(d.series) }), h, 'Leads');
        },

        standards: function (d, h) {
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: [{ name: 'Students', data: nums((d.series && d.series[0] && d.series[0].data) || d.series) }],
                colors: [C.primary],
                plotOptions: {
                    bar: { horizontal: true, borderRadius: 5, barHeight: '58%', dataLabels: { position: 'top' } },
                },
                dataLabels: {
                    enabled: true,
                    offsetX: 18,
                    style: { fontSize: '11px', fontWeight: 700, colors: [C.primaryDark] },
                },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [], labels: { show: false } }),
                yaxis: { labels: { style: { colors: C.text, fontSize: '12px', fontWeight: 600 }, maxWidth: 140 } },
                legend: { show: false },
            });
        },

        branches: function (d, h) {
            var series = (d.series || []).map(function (row) {
                return { name: row.name, data: nums(row.data) };
            });
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: series,
                colors: [C.primary, C.gold],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '46%' } },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                legend: Object.assign(base(h).legend, { show: series.length > 1 }),
            });
        },

        payment_methods: function (d, h) {
            var opts = builders.plans(Object.assign({}, d, {
                series: nums(d.series),
                total: moneyShort(d.total || 0),
            }), h, 'Collected');
            opts.colors = [C.success, C.primary, C.gold, C.info, C.primaryDark, C.goldDark];
            opts.tooltip = { y: { formatter: money } };
            return opts;
        },

        batch_attendance: function (d, h) {
            setText('batch_attendance_rate', (d.rate || 0) + '%');
            var rates = nums((d.series && d.series[0] && d.series[0].data) || []);
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'bar' }),
                series: [{ name: 'Attendance', data: rates }],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        distributed: true,
                        borderRadius: 5,
                        barHeight: '58%',
                        dataLabels: { position: 'top' },
                    },
                },
                colors: rates.map(function (v) {
                    return v >= 85 ? C.success : (v >= 70 ? C.gold : C.error);
                }),
                dataLabels: {
                    enabled: true,
                    offsetX: 26,
                    formatter: function (v) { return v + '%'; },
                    style: { fontSize: '11px', fontWeight: 700, colors: [C.primaryDark] },
                },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [], max: 100, labels: { show: false } }),
                yaxis: { labels: { style: { colors: C.text, fontSize: '12px', fontWeight: 600 }, maxWidth: 150 } },
                legend: { show: false },
                tooltip: { y: { formatter: function (v) { return v + '% present'; } } },
            });
        },

        child_attendance: function (d, h) {
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'line' }),
                series: (d.series || []).map(function (row, i) {
                    return {
                        name: row.name,
                        type: i === 0 ? 'column' : 'line',
                        data: nums(row.data),
                    };
                }),
                colors: [C.primary, C.error],
                stroke: { width: [0, 3], curve: 'smooth' },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '40%' } },
                markers: { size: [0, 4], strokeColors: '#fff', strokeWidth: 2 },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                legend: Object.assign(base(h).legend, { show: true }),
            });
        },

        child_results: function (d, h) {
            setText('child_average', d.average === null || d.average === undefined ? '—' : d.average + '%');
            return Object.assign(base(h), {
                chart: Object.assign(base(h).chart, { type: 'area' }),
                series: [{ name: 'Score', data: nums((d.series && d.series[0] && d.series[0].data) || []) }],
                colors: [C.goldDark],
                stroke: { width: 3, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.03, stops: [0, 95, 100] },
                },
                markers: { size: 5, strokeColors: '#fff', strokeWidth: 2, colors: [C.gold] },
                xaxis: Object.assign(base(h).xaxis, { categories: d.labels || [] }),
                yaxis: Object.assign(base(h).yaxis, { max: 100, tickAmount: 4 }),
                legend: { show: false },
                tooltip: { y: { formatter: function (v) { return v + '%'; } } },
            });
        },
    };

    var showEmpty = function (el) {
        el.querySelector('.ivd-chart__canvas').innerHTML =
            '<div class="ivd-chart__empty"><i class="bx bx-bar-chart-alt-2"></i><span>No data yet</span></div>';
    };

    var showError = function (el, message) {
        var errorEl = el.querySelector('.ivd-chart__error');
        if (!errorEl) {
            return;
        }
        var label = errorEl.querySelector('span');
        if (label && message) {
            label.textContent = message;
        }
        errorEl.classList.remove('d-none');
    };

    var load = function (el) {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var widget = el.getAttribute('data-ivd-chart');
        var builder = builders[widget];
        var errorEl = el.querySelector('.ivd-chart__error');
        var height = parseInt(getComputedStyle(el).getPropertyValue('--ivd-chart-h'), 10) || 300;
        var url = el.getAttribute('data-url');

        if (!builder || !url || el.dataset.loading === '1' || el.dataset.loaded === '1') {
            return;
        }

        el.dataset.loading = '1';
        el.classList.remove('is-loaded');
        if (errorEl) {
            errorEl.classList.add('d-none');
        }

        fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (json) {
                if (!json || json.success === false) {
                    throw new Error((json && json.message) || 'Request failed');
                }

                var data = json.data || {};
                var canvas = el.querySelector('.ivd-chart__canvas');
                canvas.innerHTML = '';
                el.classList.add('is-loaded');

                if (!data.series || seriesSum(data.series) === 0) {
                    try { builder(data, height); } catch (e) {}
                    el.dataset.loaded = '1';
                    showEmpty(el);
                    return;
                }

                var options = builder(data, height);
                var chart = new ApexCharts(canvas, options);
                chart.render();
                el.dataset.loaded = '1';
            })
            .catch(function (err) {
                el.classList.add('is-loaded');
                el.dataset.loaded = '0';
                showError(el, 'Could not load chart.');
                if (window.console && console.warn) {
                    console.warn('[ivd-chart]', widget, err);
                }
            })
            .finally(function () {
                el.dataset.loading = '0';
            });
    };

    var init = function () {
        var charts = document.querySelectorAll('[data-ivd-chart]');

        charts.forEach(function (el) {
            var retry = el.querySelector('[data-ivd-retry]');
            if (retry) {
                retry.addEventListener('click', function () {
                    el.dataset.loaded = '0';
                    load(el);
                });
            }
        });

        var start = function () {
            charts.forEach(load);
        };

        if (typeof ApexCharts !== 'undefined') {
            start();
            return;
        }

        var tries = 0;
        var timer = setInterval(function () {
            tries += 1;
            if (typeof ApexCharts !== 'undefined') {
                clearInterval(timer);
                start();
            } else if (tries >= 50) {
                clearInterval(timer);
                charts.forEach(function (el) {
                    el.classList.add('is-loaded');
                    showError(el, 'Chart library missing.');
                });
            }
        }, 50);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
