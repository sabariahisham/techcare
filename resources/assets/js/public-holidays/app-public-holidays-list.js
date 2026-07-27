/**
 * Public Holidays List
 */

'use strict';

let dt_public_holidays;
let selectedStates = [];

// Define custom filter function BEFORE table initialization
$.fn.dataTableExt.afnFiltering.push(
  function (settings, data, dataIndex) {
    // If nothing selected, show all
    if (selectedStates.length === 0) {
      return true;
    }

    // Get row node
    if (!dt_public_holidays) return true;
    let rowNode = dt_public_holidays.row(dataIndex).node();
    if (!rowNode) return true;

    let statesData = $(rowNode).data('states') || [];
    let exceptStatesData = $(rowNode).data('states-except') || [];
    let isNational = $(rowNode).data('is-national');

    // Check if 'Nasional' is in selected states
    if (selectedStates.includes('national')) {
      if (isNational === 'true' || isNational === true) {
        return true;
      }
    }

    // Check if any selected state matches row's states
    for (let state of selectedStates) {
      if (state !== 'national') {
        if (statesData.includes(state) || exceptStatesData.includes(state)) {
          return true;
        }
      }
    }

    return false;
  }
);

// Function to update badges display
function updateBadgesDisplay() {
  let badgesContainer = document.getElementById('selected-states-badges');
  badgesContainer.innerHTML = '';

  selectedStates.forEach(state => {
    let badge = document.createElement('span');
    badge.className = 'badge bg-label-primary d-flex align-items-center gap-2';
    badge.innerHTML = `
      ${state}
      <button type="button" class="btn-close btn-sm" data-state="${state}" style="width: auto; height: auto;" aria-label="Remove"></button>
    `;
    badgesContainer.appendChild(badge);

    // Add click handler to remove button
    badge.querySelector('.btn-close').addEventListener('click', function () {
      let stateValue = this.getAttribute('data-state');
      let checkbox = document.querySelector(`.state-checkbox[value="${stateValue}"]`);
      if (checkbox) {
        checkbox.checked = false;
        updateSelectedStates();
      }
    });
  });
}

// Function to update selected states from checkboxes
function updateSelectedStates() {
  let checkboxes = document.querySelectorAll('.state-checkbox:checked');
  selectedStates = Array.from(checkboxes).map(cb => cb.value);
  updateBadgesDisplay();

  // Redraw table with new filter
  if (dt_public_holidays) {
    dt_public_holidays.draw();
  }
}

// Initialize DataTable
let table = document.querySelector('.datatables-public-holidays');
let dataTableConfig = {
  dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"{f}r>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>>',
  displayLength: 50,
  lengthMenu: [10, 25, 50, 100],
  responsive: true,
  columnDefs: [{
    targets: 0,
    searchable: false,
    orderable: false,
    render: () => ''
  }]
};

// Initialize DataTable if element exists
if (table) {
  dt_public_holidays = $(table).DataTable(dataTableConfig);

  // Listen to checkbox changes
  let checkboxes = document.querySelectorAll('.state-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedStates);
  });
}
