<!-- includes/footer.php -->
<div id="toast" class="toast"></div>
<script>
function showToast(msg, duration=2000){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), duration);
}
</script>
</body>
</html>
