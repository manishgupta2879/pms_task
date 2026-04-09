<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        $('.select2-subtext').select2({
            theme: 'bootstrap-5',
            templateResult: function(data) {
                if (!data.id || !data.element) {
                    return data.text;
                }

                var subtext = $(data.element).data('subtext');
                if (subtext) {
                    return $('<span>' + data.text + '<br><small style="color: #999;">' + subtext + '</small></span>');
                }
                return data.text;
            }
        });
    });
</script>
</div>
</body>

</html>