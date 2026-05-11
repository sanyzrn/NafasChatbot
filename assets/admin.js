/* Nafas Chatbot Pro — Admin JS */
(function ($) {
	'use strict';

	var presets  = (typeof ncpAdmin !== 'undefined') ? ncpAdmin.presets  : {};
	var i18n     = (typeof ncpAdmin !== 'undefined') ? ncpAdmin.i18n     : {};
	var ajaxUrl  = (typeof ncpAdmin !== 'undefined') ? ncpAdmin.ajaxUrl  : '';
	var nonce    = (typeof ncpAdmin !== 'undefined') ? ncpAdmin.nonce    : '';

	/* ── Tab switching ──────────────────────────────────── */
	$(document).on('click', '.ncp-tab', function () {
		var target = $(this).data('target');
		$('.ncp-tab').removeClass('active');
		$('.ncp-tab-content').removeClass('active');
		$(this).addClass('active');
		$('#' + target).addClass('active');
	});

	/* ── Color picker ↔ text sync ───────────────────────── */
	$(document).on('input', '.ncp-color-input', function () {
		var name = $(this).attr('name');
		$('input[name="' + name + '_txt"]').val($(this).val());
		updateLivePreview();
	});

	/* ── Theme Presets ──────────────────────────────────── */
	$(document).on('click', '.ncp-apply-preset', function () {
		var $btn     = $(this);
		var presetId = $btn.data('preset');
		var preset   = presets[presetId];

		if (!preset) { return; }

		// Visual: mark selected card
		$('.ncp-preset-card').removeClass('ncp-preset-active');
		$btn.closest('.ncp-preset-card').addClass('ncp-preset-active');

		// Update all color inputs instantly (local)
		applyPresetLocally(preset);

		// Persist via AJAX
		$btn.text(i18n.presetApplying || 'Applying…').prop('disabled', true);

		$.post(ajaxUrl, {
			action: 'ncp_apply_preset',
			nonce:  nonce,
			preset: presetId
		}, function (resp) {
			$btn.text(i18n.applyPreset || 'Apply Preset').prop('disabled', false);
			if (resp.success) {
				showPresetNotice(preset.label || presetId);
				updateLivePreview();
			}
		}).fail(function () {
			$btn.text(i18n.applyPreset || 'Apply Preset').prop('disabled', false);
		});
	});

	function applyPresetLocally(preset) {
		var colorMap = {
			ncp_theme_primary:       preset.ncp_theme_primary,
			ncp_theme_primary_hover: preset.ncp_theme_primary_hover,
			ncp_theme_bg_base:       preset.ncp_theme_bg_base,
			ncp_theme_bg_card:       preset.ncp_theme_bg_card,
			ncp_theme_border:        preset.ncp_theme_border,
			ncp_theme_text_base:     preset.ncp_theme_text_base,
			ncp_theme_text_muted:    preset.ncp_theme_text_muted,
			ncp_theme_control_bg:    preset.ncp_theme_control_bg,
			ncp_theme_control_hover: preset.ncp_theme_control_hover,
			ncp_theme_control_text:  preset.ncp_theme_control_text
		};
		$.each(colorMap, function (key, value) {
			if (!value) { return; }
			var $cp  = $('#' + key);
			var $txt = $('input[name="' + key + '_txt"]');
			$cp.val(value);
			$txt.val(value);
		});
		if (preset.ncp_font_family) {
			$('#ncp_font_family').val(preset.ncp_font_family);
		}
		updateLivePreview();
	}

	function showPresetNotice(label) {
		var msg = (i18n.presetApplied || 'Preset applied:') + ' ' + label;
		$('#ncp-preset-notice-text').text(msg);
		$('#ncp-preset-notice').fadeIn(200).delay(5000).fadeOut(500);
	}

	/* ── Live Preview ───────────────────────────────────── */
	$('#ncp-toggle-preview').on('click', function () {
		var $preview = $('#ncp-live-preview');
		$preview.slideToggle(250);
		if ($preview.is(':hidden')) {
			$(this).text('👁 ' + (i18n.livePreview || 'Live Preview'));
		} else {
			$(this).text('✕ ' + (i18n.livePreview || 'Live Preview'));
			updateLivePreview();
		}
	});

	function getColor(key) {
		return $('#' + key).val() || '#000';
	}

	function updateLivePreview() {
		if ($('#ncp-live-preview').is(':hidden')) { return; }
		var panel   = $('#ncp-preview-panel');
		var primary = getColor('ncp_theme_primary');
		var bgBase  = getColor('ncp_theme_bg_base');
		var bgCard  = getColor('ncp_theme_bg_card');
		var border  = getColor('ncp_theme_border');
		var txtBase = getColor('ncp_theme_text_base');
		var txtMut  = getColor('ncp_theme_text_muted');
		var ctrlBg  = getColor('ncp_theme_control_bg');
		var font    = $('#ncp_font_family').val() || 'sans-serif';

		panel.css({
			'background': bgBase,
			'border-color': border,
			'font-family': font,
			'color': txtBase
		});
		panel.find('.ncp-preview-header').css({
			'background': primary,
			'color': '#fff'
		});
		panel.find('.ncp-preview-msg.ncp-preview-bot').css({
			'background': bgCard,
			'border-color': border,
			'color': txtBase
		});
		panel.find('.ncp-preview-msg.ncp-preview-user').css({
			'background': primary,
			'color': '#fff'
		});
		panel.find('.ncp-preview-footer').css({
			'background': ctrlBg,
			'border-color': border
		});
		panel.find('.ncp-preview-input-area').css({ 'color': txtMut });
		panel.find('.ncp-preview-send').css({
			'background': primary,
			'color': '#fff'
		});
	}

}(jQuery));
