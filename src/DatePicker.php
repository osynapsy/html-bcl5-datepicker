<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Bcl5\DatePicker;

use Osynapsy\Html\Component\AbstractComponent;
use Osynapsy\Bcl4\TextBox;

class Bcl5DatePicker extends AbstractComponent
{
    const FORMAT_DATE_IT = 'dd/MM/yyyy';
    const FORMAT_DATETIME_IT = 'dd/MM/yyyy HH:mm';

    private $datePickerId;
    private $dateComponent;
    protected $defaultValue;
    protected $format = self::FORMAT_DATE_IT;
    protected $disabledDates = [];

    public function __construct($id)
    {
        parent::__construct('div', $id.'_datepicker');
        $this->datePickerId = $id;

        // Assets Tempus Dominus v6 + PopperJS v2 (No Moment.js / No jQuery required)
        $this->requireCss('lib/popperjs-2.11.8/popper.min.js');
        $this->requireCss('lib/tempus-dominus-6.9.4/css/tempus-dominus.min.css');
        $this->requireJs('lib/tempus-dominus-6.9.4/js/tempus-dominus.min.js');
        $this->requireJs('bcl5/datepicker/script.js');

        $this->attributes([
            'class' => 'input-group date bcl5-date-picker datetimepickers osy-component',
            'data-target-input' => 'nearest'
        ]);
        $this->attribute('data-init', 'Bcl5DatePicker.initDatePickerComponent');

        $this->dateComponent = $this->add($this->fieldDateBoxFactory());
        $this->fieldInputGroupAppendFactory();
    }

    protected function fieldInputGroupAppendFactory()
    {
        $this->add('<div class="input-group-append" data-td-target="#'.$this->id.'" data-td-toggle="datetimepicker"><div class="input-group-text"><i class="fa fa-calendar"></i></div></div>');
    }

    protected function fieldDateBoxFactory()
    {
        $id = $this->datePickerId;

        // Input visibile per l'utente (mostra dd/MM/yyyy)
        $TextBox = new class ($id . '_visible') extends TextBox
        {
            public function __construct($id)
            {
                $this->formatValueFunction = [$this, 'formatDateValue'];
                parent::__construct($id);
                $this->attributes([
                    'class' => 'form-control datetimepicker-input text-center',
                    'data-td-toggle' => 'datetimepicker',
                    'data-td-target' => sprintf('#%s_datepicker', str_replace('_visible', '', $id))
                ]);
            }

            public function formatDateValue($value)
            {
                foreach (['d/m/Y', 'Y-m-d'] as $f) {
                    $odatetime = \DateTime::createFromFormat($f, $value);
                    if ($odatetime) {
                        return $odatetime->format('d/m/Y');
                    }
                }
                return $value;
            }
        };

        // Input nascosto che invierà il valore Y-m-d nel $_POST[$id]
        $this->add(sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s" class="datetimepicker-post-val">',
            $id,
            $id,
            $this->formatIsoValue($TextBox->getValue())
        ));

        return $TextBox;
    }

    private function formatIsoValue($value)
    {
        if (empty($value)) {
            return '';
        }
        foreach (['d/m/Y', 'Y-m-d'] as $f) {
            $odatetime = \DateTime::createFromFormat($f, $value);
            if ($odatetime) {
                return $odatetime->format('Y-m-d');
            }
        }
        return $value;
    }

    public function preBuild()
    {
        $this->attribute('data-format', $this->format);

        // Assegna le date disabilitate in formato JSON se presenti
        if (!empty($this->disabledDates)) {
            $this->attribute('data-disabled-dates', json_encode($this->disabledDates));
        }

        if (!empty($this->defaultValue) && empty($this->getTextBox()->getValue())) {
            $this->getTextBox()->setValue($this->defaultValue);
        }
    }

    public function getTextBox()
    {
        return $this->dateComponent;
    }

    /**
     * Imposta le date o i periodi da disabilitare.
     *
     * Esempio di utilizzo:
     * $datePicker->setDisabledDates([
     *     ['2026-03-01', '2026-03-10'], // Intervallo
     *     ['2026-03-15'],              // Data singola
     *     ['2026-04-01', '2026-04-05']  // Intervallo
     * ]);
     *
     * @param array $datesArray
     */
    public function setDisabledDates(array $datesArray)
    {
        $this->disabledDates = $datesArray;
    }

    /**
     * @param mixed $min ISO DATE (YYYY-MM-DD) o selector ID di un altro componente (#id)
     * @param mixed $max ISO DATE (YYYY-MM-DD) o selector ID di un altro componente (#id)
     */
    public function setDateLimit($min, $max)
    {
        $this->setDateMin($min);
        $this->setDateMax($max);
    }

    /**
     * @param mixed $date ISO DATE (YYYY-MM-DD) o selector ID (#id)
     */
    public function setDateMax($date)
    {
        $this->dateComponent->attribute('data-max', $date);
    }

    /**
     * @param mixed $date ISO DATE (YYYY-MM-DD) o selector ID (#id)
     */
    public function setDateMin($date)
    {
        $this->dateComponent->attribute('data-min', $date);
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setDefaultDate($date = null)
    {
        $this->defaultValue = empty($date) ? date('d/m/Y') : $date;
    }

    public function onChange($code)
    {
        $this->attribute('onchange', $code);
    }

    public function setAction($action, $parameters = null, $confirmMessage = null, $class = 'change-execute')
    {
        parent::setAction($action, $parameters, $confirmMessage, $class);
    }

    public function setDisabled($condition)
    {
        $this->dateComponent->setDisabled($condition);
    }

    public function setReadOnly($condition)
    {
        $this->dateComponent->setReadOnly($condition);
    }

    public function setWidgetPosition($position)
    {
        $this->attribute('data-widget-positioning', $position);
    }
}
