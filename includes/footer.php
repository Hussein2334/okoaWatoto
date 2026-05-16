<?php
// includes/footer.php
?>
</main>

<footer class="w-full px-4 md:px-12 py-8 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-[#c4c6cf] bg-gray-100 mt-auto">
    <div>
        <div class="font-bold text-2xl text-[#002045] mb-2">OkoaWatoto</div>
        <p class="text-sm text-[#43474e]">© 2026 Jamhuri ya Muungano wa Tanzania. Huduma ya Umma.</p>
    </div>
    <div class="flex flex-wrap gap-6 items-start md:justify-end">
        <a href="tel:112" class="text-[#43474e] text-sm hover:text-[#002045] underline">Msaada: 112</a>
        <a href="#" class="text-[#43474e] text-sm hover:text-[#002045] underline">Sera ya Faragha</a>
        <a href="#" class="text-[#43474e] text-sm hover:text-[#002045] underline">Tovuti Kuu ya Serikali</a>
        <a href="#" class="text-[#43474e] text-sm hover:text-[#002045] underline">Vituo vya Polisi</a>
    </div>
</footer>

<?php if(isset($_SESSION['success_message'])): ?>
<script>
    alert('<?php echo addslashes($_SESSION['success_message']); ?>');
</script>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
<script>
    alert('Error: <?php echo addslashes($_SESSION['error_message']); ?>');
</script>
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

</body>
</html>