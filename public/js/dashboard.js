document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.summary-box .close-btn').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.summary-box');
      if (card) card.style.display = 'none';
    });
  });

  const generateBtn = document.querySelector('.generate-report-btn');
  if (generateBtn) {
    generateBtn.addEventListener('click', () => {
      const url = generateBtn.dataset.reportUrl;
      if (url) window.location.href = url;
    });
  }

  const userDropdown = document.querySelector('.user-dropdown');
  const dropdownContent = userDropdown?.querySelector('.dropdown-content');
  if (userDropdown && dropdownContent) {
    userDropdown.addEventListener('click', event => {
      event.stopPropagation();
      dropdownContent.classList.toggle('show');
    });

    window.addEventListener('click', event => {
      if (!userDropdown.contains(event.target)) {
        dropdownContent.classList.remove('show');
      }
    });
  }

  initCharts();
});

function initCharts() {
  if (!window.Chart || !window.dashboardData) return;

  const { inventoryBar = [], salesPie = [] } = window.dashboardData;

  const inventoryCanvas = document.getElementById('inventoryBarChart');
  const inventoryEmpty = document.querySelector('[data-empty-target="inventory"]');
  if (inventoryCanvas) {
    if (Array.isArray(inventoryBar) && inventoryBar.length > 0) {
      inventoryCanvas.style.display = 'block';
      if (inventoryEmpty) inventoryEmpty.classList.add('hidden');

      const labels = inventoryBar.map(item => item.name);
      const data = inventoryBar.map(item => Number(item.quantity || 0));

      new Chart(inventoryCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Quantity on Hand',
            data,
            backgroundColor: '#6b3c2f',
            borderRadius: 4,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
          },
          plugins: {
            legend: {
              display: false,
            },
          },
        },
      });
    } else {
      inventoryCanvas.style.display = 'none';
      if (inventoryEmpty) inventoryEmpty.classList.remove('hidden');
    }
  }

  const salesCanvas = document.getElementById('salesPieChart');
  const salesEmpty = document.querySelector('[data-empty-target="sales"]');
  if (salesCanvas) {
    if (Array.isArray(salesPie) && salesPie.length > 0) {
      salesCanvas.style.display = 'block';
      if (salesEmpty) salesEmpty.classList.add('hidden');

      const labels = salesPie.map(item => item.name);
      const data = salesPie.map(item => Number(item.quantity || 0));
      const colors = ['#6b3c2f', '#b47a5a', '#e2b99c', '#a46045', '#d9825f'];

      new Chart(salesCanvas.getContext('2d'), {
        type: 'pie',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: colors.slice(0, data.length),
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
              },
            },
          },
        },
      });
    } else {
      salesCanvas.style.display = 'none';
      if (salesEmpty) salesEmpty.classList.remove('hidden');
    }
  }
}

