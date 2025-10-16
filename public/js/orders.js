// orders.js (Updated renderOrdersTable function)

function renderOrdersTable(orders) {
    tableBody.innerHTML = '';
    
    if (orders.length === 0) {
         tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 15px;">No orders found.</td></tr>';
         return;
    }

    orders.forEach(order => {
        const row = document.createElement('tr');
        const statusColor = order.order_status === 'Completed' ? 'green' : (order.order_status === 'Pending' ? 'orange' : 'red');
        
        // Safely extract data using optional chaining or null checks
        const createdBy = order.user 
            ? `${order.user.first_name} ${order.user.last_name.charAt(0)}.` 
            : 'System/Unknown'; // Handle case where user data is missing

        row.innerHTML = `
            <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.id}</td>
            <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_type}</td>
            <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_date.substring(0, 10)}</td>
            <td style="padding: 10px; border-bottom: 1px solid #eee;"><span style="color: ${statusColor};">${order.order_status}</span></td>
            <td style="padding: 10px; border-bottom: 1px solid #eee;">${createdBy}</td>
            <td style="padding: 10px; border-bottom: 1px solid #eee;"><button>View Details</button></td>
        `;
        tableBody.appendChild(row);
    });
}