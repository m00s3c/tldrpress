(function ($) {
	'use strict';

	function setStatus($scope, message, isError) {
		$scope.find('.tldrpress-status').text(message || '').toggleClass('is-error', !!isError);
	}

	$(document).on('click', '.tldrpress-generate-button', function () {
		var $box = $(this).closest('.tldrpress-meta-box');
		var $spinner = $box.find('.spinner');

		$box.find('.tldrpress-generate-button').prop('disabled', true);
		$spinner.addClass('is-active');
		setStatus($box, tldrpressAdmin.i18n.generating, false);

		$.post(tldrpressAdmin.ajaxUrl, {
			action: 'tldrpress_generate_summary',
			nonce: tldrpressAdmin.nonce,
			post_id: $box.data('post-id'),
			type: $('#tldrpress_summary_type').val(),
			length: $('#tldrpress_summary_length').val()
		}).done(function (response) {
			if (response.success) {
				$('#tldrpress_summary').val(response.data.summary);
				setStatus($box, tldrpressAdmin.i18n.complete, false);
				return;
			}

			setStatus($box, response.data && response.data.message ? response.data.message : tldrpressAdmin.i18n.error, true);
		}).fail(function (xhr) {
			var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : tldrpressAdmin.i18n.error;
			setStatus($box, message, true);
		}).always(function () {
			$box.find('.tldrpress-generate-button').prop('disabled', false);
			$spinner.removeClass('is-active');
		});
	});

	$(document).on('click', '#tldrpress-bulk-start', function () {
		var $button = $(this);
		var $status = $('.tldrpress-bulk-status');
		var $bar = $('.tldrpress-progress span');
		var mode = $('#tldrpress-bulk-mode').val();
		var offset = 0;
		var generated = 0;
		var errors = [];

		$button.prop('disabled', true);
		$bar.css('width', '0%');
		$status.text(tldrpressAdmin.i18n.generating);

		function runBatch() {
			$.post(tldrpressAdmin.ajaxUrl, {
				action: 'tldrpress_bulk_generate',
				nonce: tldrpressAdmin.nonce,
				mode: mode,
				offset: offset
			}).done(function (response) {
				if (!response.success) {
					$status.text(response.data && response.data.message ? response.data.message : tldrpressAdmin.i18n.error);
					$button.prop('disabled', false);
					return;
				}

				offset = response.data.offset;
				generated += response.data.processed;
				errors = errors.concat(response.data.errors || []);

				var total = mode === 'missing' ? generated + Math.max(0, (response.data.total || 0) - response.data.processed) : (response.data.total || generated);
				var percent = total ? Math.min(100, Math.round((generated / total) * 100)) : 100;
				$bar.css('width', percent + '%');
				$status.text(generated + ' / ' + total + ' processed');

				if (response.data.done) {
					$bar.css('width', '100%');
					$status.text(tldrpressAdmin.i18n.complete + '. ' + generated + ' processed' + (errors.length ? '. Errors: ' + errors.join('; ') : '.'));
					$button.prop('disabled', false);
					return;
				}

				runBatch();
			}).fail(function (xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : tldrpressAdmin.i18n.error;
				$status.text(message);
				$button.prop('disabled', false);
			});
		}

		runBatch();
	});
})(jQuery);
