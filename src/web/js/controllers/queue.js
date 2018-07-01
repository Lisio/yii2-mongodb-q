app.controller.queue = {
    actionIndex: function() {
        $('.js-purge').click(function() {
            if (!confirm('Purge this queue?')) {
                return false;
            }

            $(this).siblings('button').addBack().attr('disabled', 'disabled');

            app.controller.queue._updateQueue($(this).data('queue'), app.router.ajaxQueuePurge);
        });

        $('.js-remove').click(function() {
            if (!confirm('Remove this queue?')) {
                return false;
            }

            $(this).siblings('button').addBack().attr('disabled', 'disabled');

            app.controller.queue._updateQueue($(this).data('queue'), app.router.ajaxQueueRemove);
        });
    },
    actionJobs: function() {
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

            app.controller.job._updateJobStatus($(this).data('id'), app.router.ajaxJobRemove);
        });
    },
    _updateQueue: function(name, ajaxURL, successURL) {
        $.ajax({
            url: ajaxURL,
            method: 'POST',
            data: {name: name},
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
