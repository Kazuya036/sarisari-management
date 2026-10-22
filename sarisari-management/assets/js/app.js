document.addEventListener('DOMContentLoaded',()=>{
 const toggle=document.getElementById('menuToggle'),sidebar=document.getElementById('sidebar');
 if(toggle&&sidebar)toggle.addEventListener('click',()=>sidebar.classList.toggle('open'));
 document.querySelectorAll('form').forEach(form=>{form.addEventListener('submit',()=>{const btn=form.querySelector('button[type="submit"],button:not([type])');if(btn&&!form.querySelector('[name="action"][value="adjust"]')){btn.dataset.originalText=btn.textContent.trim();btn.disabled=true;btn.classList.add('is-loading');btn.textContent='Please wait…';}});});
 document.querySelectorAll('.alert').forEach(alert=>{setTimeout(()=>{alert.classList.add('alert-hide');},5000);});
});
