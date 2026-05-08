// Render Order View from JSON API into the page
function loadOrderView({ id, endpoint }) {
  const $ = (sel) => document.querySelector(sel);
  const alertEl = $('#alert');
  const contentEl = $('#content');
  const itemsEl = $('#items');

  function money(n) { return (Number(n)||0).toFixed(2); }

  function setAlert(msg) {
    alertEl.textContent = msg;
    alertEl.style.display = msg ? 'block' : 'none';
  }

  function fill(order) {
    contentEl.style.display = 'block';
    $('#ov-id').textContent = order.order.id || id;
    $('#ov-datetime').textContent = order.order.datetime || '—';
    $('#ov-type').textContent = order.order.type || '—';
    $('#ov-status').textContent = order.order.status || '—';
    $('#ov-remarks').textContent = order.order.remarks || '—';
    $('#ov-staff').textContent = order.order.staff || '—';
    $('#ov-points').textContent = order.order.points || '—';

    $('#pay-total').textContent = money(order.totals.total);
    $('#pay-method').textContent = order.payment?.method || '—';
    $('#pay-status').textContent = order.payment?.status || '—';
    $('#pay-ref').textContent = order.payment?.reference || '—';

    itemsEl.innerHTML = '';
    (order.items || []).forEach(it => {
      const li = document.createElement('li');
      const left = document.createElement('div');
      left.style.display = 'flex'; left.style.alignItems = 'center';
      if (it.image) {
        const img = document.createElement('img'); img.src = it.image; left.appendChild(img);
      }
      const name = document.createElement('div');
      name.innerHTML = `<div><strong>${it.name || 'Item'}</strong></div><div class="muted">x${it.qty} @ ₱${money(it.price)}</div>`;
      name.style.marginLeft = '10px';
      left.appendChild(name);
      const right = document.createElement('div');
      right.innerHTML = `<strong>₱${money(it.subtotal)}</strong>`;
      li.appendChild(left); li.appendChild(right);
      itemsEl.appendChild(li);
    });
  }

  async function load() {
    try {
      setAlert(''); contentEl.style.display = 'none';
      const res = await fetch(`${endpoint}?id=${encodeURIComponent(id)}`);
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to load order');
      fill(data);
    } catch (e) {
      setAlert(e.message || 'This order cannot be found or may have been removed.');
    }
  }

  load();
}
