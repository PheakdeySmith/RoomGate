'use strict';

(function () {
  if (typeof window.Helpers !== 'undefined') {
    window.Helpers.initCustomOptionCheck();
  }

  const wizardPropertyListing = document.querySelector('#wizard-property-listing');
  if (!wizardPropertyListing) {
    return;
  }

  const wizardForm = wizardPropertyListing.querySelector('#wizard-property-listing-form');
  const stepper = new Stepper(wizardPropertyListing, { linear: true });

  const FormValidation1 = FormValidation.formValidation(
    wizardForm.querySelector('#personal-details'),
    {
      fields: {
        'extra[account_type]': {
          validators: {
            notEmpty: { message: 'Please choose account type' }
          }
        },
        'extra[contact_number]': {
          validators: {
            notEmpty: { message: 'Please enter contact number' }
          }
        },
        'extra[first_name]': {
          validators: {
            notEmpty: { message: 'Please enter first name' }
          }
        },
        'extra[last_name]': {
          validators: {
            notEmpty: { message: 'Please enter last name' }
          }
        },
        tenant_name: {
          validators: {
            callback: {
              message: 'Please enter organization name',
              callback: function (input) {
                const selected = document.querySelector('input[name="extra[account_type]"]:checked');
                if (selected && selected.value === 'organization') {
                  return input.value.trim().length > 0;
                }
                return true;
              }
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton()
      }
    }
  ).on('core.form.valid', function () {
    stepper.next();
  });

  const FormValidation2 = FormValidation.formValidation(
    wizardForm.querySelector('#property-details'),
    {
      fields: {
        property_name: {
          validators: {
            notEmpty: { message: 'Please enter property name' }
          }
        },
        property_type: {
          validators: {
            notEmpty: { message: 'Please select property type' }
          }
        },
        country: {
          validators: {
            notEmpty: { message: 'Please select country' }
          }
        },
        state_province: {
          validators: {
            notEmpty: { message: 'Please enter state/province' }
          }
        },
        city: {
          validators: {
            notEmpty: { message: 'Please enter city' }
          }
        },
        postal_code: {
          validators: {
            notEmpty: { message: 'Please enter zip code' }
          }
        },
        address_line_1: {
          validators: {
            notEmpty: { message: 'Please enter address' }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton()
      }
    }
  ).on('core.form.valid', function () {
    stepper.next();
  });

  const countrySelect = $('#plCountry');
  if (countrySelect.length) {
    countrySelect.wrap('<div class="position-relative"></div>');
    countrySelect
      .select2({ placeholder: 'Select country', dropdownParent: countrySelect.parent() })
      .on('change', function () {
        FormValidation2.revalidateField('country');
      });
  }

  const propertyTypeSelect = $('#plPropertyType');
  if (propertyTypeSelect.length) {
    propertyTypeSelect.wrap('<div class="position-relative"></div>');
    propertyTypeSelect
      .select2({ placeholder: 'Select property type', dropdownParent: propertyTypeSelect.parent() })
      .on('change', function () {
        FormValidation2.revalidateField('property_type');
      });
  }

  const nextButtons = [].slice.call(wizardForm.querySelectorAll('.btn-next'));
  const prevButtons = [].slice.call(wizardForm.querySelectorAll('.btn-prev'));
  const orgField = document.getElementById('orgNameField');
  const accountRadios = document.querySelectorAll('input[name=\"extra[account_type]\"]');

  const syncOrgField = () => {
    if (!orgField) return;
    const selected = document.querySelector('input[name=\"extra[account_type]\"]:checked');
    const isOrg = selected && selected.value === 'organization';
    orgField.style.display = isOrg ? '' : 'none';
  };

  if (accountRadios.length) {
    accountRadios.forEach(radio => {
      radio.addEventListener('change', syncOrgField);
      radio.addEventListener('change', () => {
        FormValidation1.revalidateField('extra[account_type]');
        FormValidation1.revalidateField('tenant_name');
      });
    });
    syncOrgField();
  }

  nextButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      switch (stepper._currentIndex) {
        case 0:
          FormValidation1.validate();
          break;
        case 1:
          FormValidation2.validate();
          break;
        default:
          wizardForm.submit();
          break;
      }
    });
  });

  prevButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      stepper.previous();
    });
  });
})();
