</main><footer class="auth-bottom">© <?= date('Y') ?> Gia Dụng Shop</footer>
<script>document.querySelectorAll('[data-password]').forEach(button=>button.addEventListener('click',()=>{const field=document.getElementById(button.dataset.password),show=field.type==='password';field.type=show?'text':'password';button.textContent=show?'Ẩn':'Hiện';button.setAttribute('aria-label',show?'Ẩn mật khẩu':'Hiện mật khẩu');button.setAttribute('aria-pressed',String(show));}));</script>
</body></html>
