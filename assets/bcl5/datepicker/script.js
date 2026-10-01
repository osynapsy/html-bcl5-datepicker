const Bcl5DatePicker = 
{
    init: function() {
        // Seleziona tutti gli elementi corrispondenti
        document.querySelectorAll('.bcl5-date-picker, .datetimepickers').forEach(el => {
            this.initDatePickerComponent(el);
        });

        // Event delegation nativa sul document (o body) per intercettare 'td.change'
        document.body.addEventListener('td.change', e => {
            const container = e.target.closest('.bcl5-date-picker');
            if (!container) return;

            const hiddenInput = container.querySelector('input[type="hidden"].datetimepicker-post-val');
            
            if (hiddenInput) {
                if (e.detail && e.detail.date) {
                    // e.detail.date è un oggetto DateTime di Tempus Dominus
                    const d = e.detail.date;
                    const year = d.year;
                    const month = String(d.month + 1).padStart(2, '0'); // i mesi sono 0-indexed
                    const day = String(d.date).padStart(2, '0');
                    
                    hiddenInput.value = `${year}-${month}-${day}`;
                } else {
                    hiddenInput.value = '';
                }
            }

            if (container.classList.contains('change-execute') && window.Osynapsy) {
                Osynapsy.action.execute(container);
            }

            // Lancia un evento nativo 'change' per notificare eventuali ascoltatori
            container.dispatchEvent(new Event('change', { bubbles: true }));
        });
    },

    parseDisabledDates: function(data) {
        if (!data) return [];
        let ranges = typeof data === 'string' ? JSON.parse(data || '[]') : data;
        let dates = [];

        ranges.forEach(item => {
            if (!Array.isArray(item) || !item.length) return;
            let start = new Date(item[0]);
            let end = item[1] ? new Date(item[1]) : start;

            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                dates.push(new Date(d));
            }
        });

        return dates;
    },

    initDatePickerComponent: function(self) {
        const el = self;
        const inputEl = el.querySelector('input[type="text"]') || el;

        // Recupera i dataset HTML5 (sostituisce $.data())
        const elData = el.dataset || {};
        const inputData = inputEl.dataset || {};

        let options = {
            display: {
                components: {
                    decades: true,
                    year: true,
                    month: true,
                    date: true,
                    hours: false,
                    minutes: false,
                    seconds: false
                }
            },
            localization: {
                locale: 'it',
                format: elData.format || 'dd/MM/yyyy'
            },
            restrictions: {}
        };

        // Gestione minDate e maxDate
        ['min', 'max'].forEach(type => {
            let val = elData[type] || inputData[type];
            if (!val) return;

            let restrictionKey = type + 'Date';

            if (val.startsWith('#')) {
                const targetEl = document.querySelector(val);
                if (targetEl) {
                    targetEl.addEventListener('td.change', e => {
                        let instance = tempusDominus.TempusDominus.getInstance(inputEl);
                        if (instance && e.detail && e.detail.date) {
                            instance.updateOptions({
                                restrictions: {
                                    [restrictionKey]: e.detail.date
                                }
                            });
                        }
                    });
                }
            } else {
                options.restrictions[restrictionKey] = new Date(val);
            }
        });

        // Gestione date disabilitate
        let disabledData = elData.disabledDates || elData.disabled || inputData.disabledDates;
        if (disabledData) {
            let list = this.parseDisabledDates(disabledData);
            if (list.length) {
                options.restrictions.disabledDates = list;
            }
        }

        new tempusDominus.TempusDominus(inputEl, options);
    }
};

if (window.Osynapsy) {
    Osynapsy.plugin.register('Bcl5DatePicker', () => Bcl5DatePicker.init());
}