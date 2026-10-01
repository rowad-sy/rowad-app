/*
 * إعدادات مشتركة لكل الرسوم (Chart.js): خط Tajawal، ألوان نص وشبكة تتبع الوضع الفاتح/الداكن، تلميحات مُنسّقة، حركة دخول هادئة.
 * عرض فقط: لا تغيّر البيانات ولا ألوان الشرائح المحددة في كل صفحة.
 */
import Chart from 'chart.js/auto';

const css = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

function applyDefaults() {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    Chart.defaults.font.family = "'Tajawal', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = css('--color-text-muted', '#6B7280');
    Chart.defaults.borderColor = css('--color-border', '#E5E7EB');
    Chart.defaults.animation = reduced ? false : { duration: 900, easing: 'easeOutQuart' };
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.textDirection = 'rtl';
    Chart.defaults.plugins.tooltip.backgroundColor = css('--color-card', '#fff');
    Chart.defaults.plugins.tooltip.titleColor = css('--color-text-main', '#1F2937');
    Chart.defaults.plugins.tooltip.bodyColor = css('--color-text-main', '#1F2937');
    Chart.defaults.plugins.tooltip.borderColor = css('--color-border', '#E5E7EB');
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.elements.arc.borderWidth = 2;
    Chart.defaults.elements.arc.borderColor = css('--color-card', '#fff');
    Chart.defaults.elements.bar.borderRadius = 6;
    // حلقيات أنعم: فتحة أوسع وفواصل صغيرة وتكبير عند التحويم
    Chart.defaults.datasets.doughnut.cutout = '68%';
    Chart.defaults.datasets.doughnut.spacing = 3;
    Chart.defaults.datasets.doughnut.borderRadius = 6;
    Chart.defaults.datasets.doughnut.hoverOffset = 6;
    Chart.defaults.datasets.bar.maxBarThickness = 42;
    // شبكة ومحاور خافتة
    Chart.defaults.scale.grid.color = css('--color-border', '#E5E7EB');
    Chart.defaults.scale.grid.tickLength = 0;
    Chart.defaults.scale.border.color = 'transparent';
}

applyDefaults();
// عند تبديل الوضع الفاتح/الداكن تُحدَّث ألوان الرسوم القائمة
new MutationObserver(() => {
    applyDefaults();
    Object.values(Chart.instances).forEach((c) => { c.update('none'); });
}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

window.Chart = Chart;
