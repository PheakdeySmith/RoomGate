@extends('admin::components.layouts.master')
@section('title', 'Translations | RoomGate Admin')
@section('page-title', 'Translations')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-translations table border-top">
        <thead>
          <tr>
            <th></th>
            <th></th>
            <th>Key</th>
            <th>English</th>
            <th>Khmer</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rows as $row)
            <tr>
              <td></td>
              <td></td>
              <td class="text-nowrap text-heading">{{ $row['key'] }}</td>
              <td>{{ $row['en'] }}</td>
              <td>{{ $row['km'] }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon me-1"
                    data-bs-target="#editTranslationModal"
                    data-bs-toggle="modal"
                    data-translation-key="{{ $row['key'] }}"
                    data-translation-en="{{ $row['en'] }}"
                    data-translation-km="{{ $row['km'] }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.translations.destroy', $row['key']) }}" data-confirm="Delete this translation?">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-icon" type="submit">
                      <i class="icon-base ti tabler-trash icon-22px"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="addTranslationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Add Translation</h3>
            <p class="text-body-secondary">Add a new translation key and values.</p>
          </div>
          <form class="row g-3" method="POST" action="{{ route('admin.translations.store') }}">
            @csrf
            <div class="col-12">
              <label class="form-label" for="translationKey">Key</label>
              <input type="text" id="translationKey" name="key" class="form-control" placeholder="menu.roles" />
            </div>
            <div class="col-12">
              <label class="form-label" for="translationEn">English</label>
              <input type="text" id="translationEn" name="en" class="form-control" placeholder="Roles" />
            </div>
            <div class="col-12">
              <label class="form-label" for="translationKm">Khmer</label>
              <input type="text" id="translationKm" name="km" class="form-control" placeholder="តួនាទី" />
            </div>
            <div class="col-12 text-center mt-6">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Save</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editTranslationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Edit Translation</h3>
            <p class="text-body-secondary">Update translation values.</p>
          </div>
          <form class="row g-3" method="POST" id="editTranslationForm">
            @csrf
            @method('PATCH')
            <div class="col-12">
              <label class="form-label" for="editTranslationKey">Key</label>
              <input type="text" id="editTranslationKey" name="key" class="form-control" />
            </div>
            <div class="col-12">
              <label class="form-label" for="editTranslationEn">English</label>
              <input type="text" id="editTranslationEn" name="en" class="form-control" />
            </div>
            <div class="col-12">
              <label class="form-label" for="editTranslationKm">Khmer</label>
              <input type="text" id="editTranslationKm" name="km" class="form-control" />
            </div>
            <div class="col-12 text-center mt-6">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Update</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-translations');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[2, 'asc']],
          columnDefs: [
            {
              targets: 0,
                className: 'control',
              orderable: false,
              searchable: false,
              render: function () {
                return '';
              }
            }
          ],
          layout: {
            topStart: {
              rowClass: 'row m-3 my-0 justify-content-between',
              features: [
                {
                  pageLength: {
                    menu: [10, 25, 50, 100],
                    text: 'Show _MENU_'
                  }
                }
              ]
            },
            topEnd: {
              features: [
                {
                  search: {
                    placeholder: 'Search Translations',
                    text: '_INPUT_'
                  }
                },
                {
                  buttons: [
                    {
                      text: '<i class="icon-base ti tabler-plus icon-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Add Translation</span>',
                      className: 'add-new btn btn-primary',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addTranslationModal'
                      }
                    }
                  ]
                }
              ]
            },
            bottomStart: {
              rowClass: 'row mx-3 justify-content-between',
              features: ['info']
            },
            bottomEnd: 'paging'
          },
          language: {
            paginate: {
              next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
              previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
              first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
              last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
            }
          },
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'Translation Details';
                }
              }),
              type: 'column'
            }
          }
        });
      }

      setTimeout(() => {
        const elementsToModify = [
          { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
          { selector: '.dt-search', classToAdd: 'me-4' },
          { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
          { selector: '.dt-length', classToAdd: 'mb-0 mb-md-5' },
          { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
          { selector: '.dt-buttons', classToAdd: 'mb-0 w-auto' },
          { selector: '.dt-layout-start', classToAdd: 'mt-0 px-5' },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-md-4 mb-sm-0 mb-6 mt-0'
          },
          { selector: '.dt-layout-start', classToAdd: 'mt-0' },
          { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
          { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
        ];

        elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
          document.querySelectorAll(selector).forEach(element => {
            if (classToRemove) {
              classToRemove.split(' ').forEach(className => element.classList.remove(className));
            }
            if (classToAdd) {
              classToAdd.split(' ').forEach(className => element.classList.add(className));
            }
          });
        });
      }, 100);

      const editModal = document.getElementById('editTranslationModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const key = trigger.getAttribute('data-translation-key');
          const en = trigger.getAttribute('data-translation-en');
          const km = trigger.getAttribute('data-translation-km');
          const form = document.getElementById('editTranslationForm');

          form.action = `{{ url('/admin/translations') }}/${encodeURIComponent(key)}`;
          document.getElementById('editTranslationKey').value = key;
          document.getElementById('editTranslationEn').value = en || '';
          document.getElementById('editTranslationKm').value = km || '';
        });
      }
    });
  </script>
@endpush
