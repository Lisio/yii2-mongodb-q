app.controller.job = {
    actionView: function() {
        $('.js-pause').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            app.controller.job._updateJobStatus($(this).data('id'), app.router.ajaxJobPause);
        });

        $('.js-resume').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            app.controller.job._updateJobStatus($(this).data('id'), app.router.ajaxJobResume);
        });

        $('.js-retry').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            app.controller.job._updateJobStatus($(this).data('id'), app.router.ajaxJobRetry);
        });

        $('.js-remove').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (!confirm('Remove this job?')) {
                return false;
            }

            app.controller.job._updateJobStatus($(this).data('id'), app.router.ajaxJobRemove, app.router.queueIndex);
        });
    },
    _updateJobStatus: function(jobID, ajaxURL, successURL) {
        $.ajax({
            url: ajaxURL,
            method: 'POST',
            data: {id: jobID},
            dataType: 'json',
            success: function(res) {
                if (res.success && successURL) {
                    window.location.assign(successURL);
                } else {
                    window.location.reload();
                }
            },
            error: function() {
                window.location.reload();
            }
        });
    }
};
