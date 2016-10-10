(function ($, window, document, undefined) {
    var $doc = $(document);

    $doc.ready(function () {
        Form.initialise({
            selector: 'form'
        });
    });
})(jQuery, window, document);

var Form = (function ($) {
    var settings = {
        selector: null
    };

    function initialise(config) {
        settings = config;
        bindEvents();
        initValidation(settings.selector);
    }

    function bindEvents() {
        $(settings.selector).on('submit', function (event) {
            var $form = $(this);

            if (
                $('.LV_invalid_field:visible', $form).length ||
                $('input.required:visible', $form).value == '' ||
                $('textarea.required:visible', $form).value == '' ||
                $('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').length ||
                $('.required[type="checkbox"]:not(:checked)', $form).length
            ) {
                if (!$('select.required', $form).next('.c2-sb-wrap:visible').is('.populated')) {
                    $('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').addClass('field-error');
                }
                return false;
            }
        });
    }

    function initValidation($cnt) {
        $('[data-validators]:visible', $cnt).each(function () {
            var $self = $(this),
                fieldTitle = $self[0].title,
                validators = $self.data('validators').split('&'),
                validationObject = new LiveValidation(this.id);

            for (var i = validators.length - 1; i >= 0; i--) {
                var str = 'validationObject.add(Validate.' + validators[i] + ')';
                eval(str);
            }

            if ($self.is('.required')) {
                validationObject.add(Validate.Exclusion, {within: [fieldTitle]});
            }

            $self.data('vaildation-instance', validationObject);
        });
    }

    function destroyValidation() {
        $('[data-validators]', '.condition-hidden').each(function () {
            var $field = $(this);

            if ($field.data('vaildation-instance')) {
                $field.data('vaildation-instance').destroy();
            }
        });
    }

    return {
        initialise: initialise
    };
}(jQuery));
