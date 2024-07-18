const apiUrl = 'http://localhost/phprest-api-home1/index.php';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('addItemBtn').addEventListener('click', addItem);
    loadItems();
    loadSoldItems();
    loadSummary();
});

// Új termék hozzáadása
async function addItem() {
    const name = document.getElementById('name').value;
    const price = document.getElementById('price').value;
    const quantity = document.getElementById('quantity').value;

    const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name, price, quantity })
    });

    if (response.ok) {
        alert('Termék sikeresen hozzáadva');
        loadItems();
        loadSummary();
    } else {
        alert('Termék hozzáadása sikertelen');
    }
}

// Termékek betöltése
async function loadItems() {
    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error('Failed to load items');
        }
        const items = await response.json();

        const tbody = document.querySelector('#items-table tbody');
        tbody.innerHTML = '';
        items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.name}</td>
                <td>${item.price}</td>
                <td>${item.quantity}</td>
                <td>
                    <button onclick="sellItem(${item.id})">Eladás</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error loading items:', error);
    }
}

// Eladott termékek betöltése
async function loadSoldItems() {
    try {
        const response = await fetch(apiUrl + '?sold=1');
        if (!response.ok) {
            throw new Error('Failed to load sold items');
        }
        const items = await response.json();

        const tbody = document.querySelector('#sold-items-table tbody');
        tbody.innerHTML = '';
        items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.name}</td>
                <td>${item.price}</td>
                <td>${item.quantity}</td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error loading sold items:', error);
    }
}

// Termék eladása
async function sellItem(id) {
    const quantity = prompt('Add meg az eladni kívánt mennyiséget:');
    if (!quantity) return;

    try {
        const response = await fetch(apiUrl + '?sell=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, quantity })
        });

        if (response.ok) {
            alert('Termék sikeresen eladva');
            loadItems();
            loadSoldItems();
            loadSummary();
        } else {
            alert('Termék eladása sikertelen');
        }
    } catch (error) {
        console.error('Error selling item:', error);
    }
}

// Összesített adatok betöltése
async function loadSummary() {
    try {
        const response = await fetch(apiUrl + '?summary=1');
        if (!response.ok) {
            throw new Error('Failed to load summary');
        }
        const summary = await response.json();

        document.getElementById('summary').innerText = `Összes termék: ${summary.item_count}, Összes ár: ${summary.total_price}`;
    } catch (error) {
        console.error('Error loading summary:', error);
    }
}
