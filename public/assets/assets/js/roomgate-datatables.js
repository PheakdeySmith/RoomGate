window.RoomGateDataTables = window.RoomGateDataTables || (function () {
  const layout = {
    topStart: {
      rowClass: 'row my-md-0 me-3 ms-0 justify-content-between',
      features: [
        {
          pageLength: {
            menu: [10, 25, 50, 100],
            text: '_MENU_'
          }
        }
      ]
    },
    topEnd: {
      features: [
        {
          search: {
            placeholder: 'Search',
            text: '_INPUT_'
          }
        }
      ]
    },
    bottomStart: {
      rowClass: 'row mx-3 justify-content-between',
      features: ['info']
    },
    bottomEnd: 'paging'
  };

  const processingHtml = '<div class="rg-dt-processing"><div class="sk-wave"><div class="sk-wave-rect"></div><div class="sk-wave-rect"></div><div class="sk-wave-rect"></div><div class="sk-wave-rect"></div><div class="sk-wave-rect"></div></div></div>';

  const language = {
    paginate: {
      next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
      previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
      first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
      last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
    },
    processing: processingHtml,
    loadingRecords: processingHtml,
    emptyTable: 'No data available in table'
  };

  function buildOptions(options) {
    const merged = Object.assign(
      {
        layout: layout,
        language: language,
        processing: false,
        responsive: {
          details: {
            display: DataTable.Responsive.display.modal({
              header: function () {
                return 'Details';
              }
            }),
            type: 'column'
          }
        }
      },
      options || {}
    );
    return merged;
  }

  function applyLayoutClasses(scope) {
    const root = scope || document;
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-buttons.btn-group .btn-group', classToRemove: 'btn-group' },
      { selector: '.dt-buttons.btn-group', classToRemove: 'btn-group', classToAdd: 'd-flex' },
      { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
      { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
      { selector: '.dt-length', classToAdd: 'mb-md-6 mb-0' },
      { selector: '.dt-layout-start', classToAdd: 'ps-3 mt-0' },
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-4 mt-0 mb-md-0 mb-6'
      },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
    ];

    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      root.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }

  return {
    layout,
    language,
    buildOptions,
    applyLayoutClasses
  };
})();
