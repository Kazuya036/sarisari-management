document.addEventListener('DOMContentLoaded', () => {
  const grid=document.getElementById('posProductGrid'), cartItems=document.getElementById('cartItems'), hiddenCart=document.getElementById('hiddenCart');
  if(!grid)return;
  const cart=new Map(), fmt=n=>'₱'+Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  const moneyNum=v=>Math.max(0,Number(v)||0);
  function render(){
    cartItems.innerHTML=''; hiddenCart.innerHTML='';
    if(!cart.size){cartItems.innerHTML='<div class="empty-state compact"><div>＋</div><strong>Your cart is empty</strong><p>Select products to start a sale.</p></div>';}
    let subtotal=0;
    cart.forEach((item,id)=>{
      const line=item.price*item.qty;subtotal+=line;
      const row=document.createElement('div');row.className='cart-item';
      row.innerHTML=`<div class="cart-item-main"><strong></strong><small></small><div class="qty-control"><button type="button" data-action="minus" data-id="${id}" aria-label="Decrease quantity">−</button><span>${item.qty}</span><button type="button" data-action="plus" data-id="${id}" aria-label="Increase quantity">+</button></div></div><div class="cart-item-end"><strong>${fmt(line)}</strong><button type="button" class="remove-item" data-action="remove" data-id="${id}">Remove</button></div>`;
      row.querySelector('.cart-item-main strong').textContent=item.name;row.querySelector('.cart-item-main small').textContent=fmt(item.price)+' / '+item.unit;
      cartItems.appendChild(row);
      const input=document.createElement('input');input.type='hidden';input.name=`cart[${id}]`;input.value=item.qty;hiddenCart.appendChild(input);
    });
    const discount=Math.min(moneyNum(document.getElementById('discount').value),subtotal),total=Math.max(0,subtotal-discount),cash=moneyNum(document.getElementById('cashReceived').value);
    document.getElementById('subtotalDisplay').textContent=fmt(subtotal);document.getElementById('totalDisplay').textContent=fmt(total);document.getElementById('changeDisplay').textContent=fmt(Math.max(0,cash-total));
  }
  grid.addEventListener('click',e=>{const b=e.target.closest('.pos-product');if(!b)return;const id=b.dataset.id,stock=Number(b.dataset.stock),item=cart.get(id);if(item){if(item.qty+1>stock){alert('No more stock available for this product.');return;}item.qty++;}else cart.set(id,{name:b.dataset.name,price:Number(b.dataset.price),stock,unit:b.dataset.unit,qty:1});render();});
  cartItems.addEventListener('click',e=>{const b=e.target.closest('button[data-action]');if(!b)return;const id=b.dataset.id,item=cart.get(id);if(!item)return;if(b.dataset.action==='remove')cart.delete(id);if(b.dataset.action==='minus'){item.qty--;if(item.qty<=0)cart.delete(id);}if(b.dataset.action==='plus'){if(item.qty+1>item.stock){alert('No more stock available for this product.');return;}item.qty++;}render();});
  document.getElementById('clearCart').addEventListener('click',()=>{cart.clear();render();});
  document.getElementById('discount').addEventListener('input',render);document.getElementById('cashReceived').addEventListener('input',render);
  document.getElementById('productSearch').addEventListener('input',e=>{const q=e.target.value.toLowerCase();let visible=0;grid.querySelectorAll('.pos-product').forEach(b=>{const show=(b.dataset.name+' '+b.dataset.sku).toLowerCase().includes(q);b.hidden=!show;if(show)visible++;});document.getElementById('noProducts').classList.toggle('hidden',visible>0);});
  document.getElementById('checkoutForm').addEventListener('submit',e=>{if(!cart.size){e.preventDefault();alert('Add at least one product first.');return;}const total=[...cart.values()].reduce((s,i)=>s+i.price*i.qty,0)-moneyNum(document.getElementById('discount').value);if(moneyNum(document.getElementById('cashReceived').value)<Math.max(0,total)){e.preventDefault();alert('Cash received must cover the total amount.');}});
  render();
});
