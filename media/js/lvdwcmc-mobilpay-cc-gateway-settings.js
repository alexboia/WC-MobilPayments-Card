(function($) {
    "use strict";

    /**
     * Define server-side payment asset upload error codes
     * */
    var UPLOAD_OK = 0;
    var UPLOAD_INVALID_MIME_TYPE = 1;
    var UPLOAD_TOO_LARGE = 2;
    var UPLOAD_NO_FILE = 3;
    var UPLOAD_INTERNAL_ERROR = 4;
    var UPLOAD_STORE_FAILED = 5;
    var UPLOAD_NOT_VALID = 6;
    var UPLOAD_FAILED = 99;

    var _isShowingProgress = false;

    var _tplAssetFileExists = null;
    var _tplAssetFileMissing = null;

    var _paymentAssetUploaders = {
        'mobilpay_live_public_cert': null,
        'mobilpay_sandbox_public_cert': null,
        'mobilpay_live_private_key': null,
        'mobilpay_sandbox_private_key': null
    };

    var _paymentAssetFileTypes = {
        'mobilpay_live_public_cert': [
            { title: 'Public key certificates', extensions: 'cer' }
        ],
        'mobilpay_sandbox_public_cert': [
            { title: 'Public key certificates', extensions: 'cer' }
        ],
        'mobilpay_live_private_key': [
            { title: 'Private keys', extensions: 'key' }
        ],
        'mobilpay_sandbox_private_key': [
            { title: 'Private keys', extensions: 'key' }
        ]
    }

    var _uploaderErrors = {
        server: {},
        client: {}
    };

    function _toastMessage(success, message) {
        if (success) {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    function _showProgress() {
        if (!_isShowingProgress) {
            lvdwcmc.showPleaseWait();
            _isShowingProgress = true;
        }
    }

    function _hideProgress() {
        if (_isShowingProgress) {
            lvdwcmc.hidePleaseWait();
            _isShowingProgress = false;
        }
    }

    function _initAssetUploader(assetId, uploader) {
        uploader.init();
        _paymentAssetUploaders[assetId] = uploader;
    }

    function _destroyAssetUploader(assetId) {
        var uploader = _paymentAssetUploaders[assetId];
        if (uploader != null) {
            uploader.unbindAll();
            uploader.destroy();
            uploader = null;
        }
        _paymentAssetUploaders[assetId] = null;
    }

    function _getAssetFileUploadUrl() {
        var url = window.lvdwcmc_uploadPaymentAssetUrl;

        url += url.indexOf('?') < 0 ? '?' : '&';
        url += 'payment_asset_upload_nonce=' + window.lvdwcmc_uploadPaymentAssetNonce;

        return url;
    }

    function _getAssetFileRemovalUrl() {
        var url = window.lvdwcmc_removePaymentAssetUrl;

        url += url.indexOf('?') < 0 ? '?' : '&';
        url += 'payment_asset_remove_nonce=' + window.lvdwcmc_removePaymentAssetNonce;

        return url;
    }

    function _getAssetContainerElement(assetId) {
        return $('#' + assetId + '_asset_container');
    }

    function _getAssetBrowseButtonElement(assetId) {
        return $('#' + assetId + '_file_selector');
    }

    function _initUploaderErrorMessages() {
        _uploaderErrors.client[plupload.FILE_SIZE_ERROR] = lvdwcmcSettingsL10n.errPluploadTooLarge;
        _uploaderErrors.client[plupload.FILE_EXTENSION_ERROR] = lvdwcmcSettingsL10n.errPluploadFileType;
        _uploaderErrors.client[plupload.IO_ERROR] = lvdwcmcSettingsL10n.errPluploadIoError;
        _uploaderErrors.client[plupload.SECURITY_ERROR] = lvdwcmcSettingsL10n.errPluploadSecurityError;
        _uploaderErrors.client[plupload.INIT_ERROR] = lvdwcmcSettingsL10n.errPluploadInitError;
        _uploaderErrors.client[plupload.HTTP_ERROR] = lvdwcmcSettingsL10n.errPluploadHttp;

        _uploaderErrors.server[UPLOAD_INVALID_MIME_TYPE] = lvdwcmcSettingsL10n.errServerUploadFileType;
        _uploaderErrors.server[UPLOAD_TOO_LARGE] = lvdwcmcSettingsL10n.errServerUploadTooLarge;
        _uploaderErrors.server[UPLOAD_NO_FILE] = lvdwcmcSettingsL10n.errServerUploadNoFile;
        _uploaderErrors.server[UPLOAD_INTERNAL_ERROR] = lvdwcmcSettingsL10n.errServerUploadInternal;
        _uploaderErrors.server[UPLOAD_FAILED] = lvdwcmcSettingsL10n.errServerUploadFail;
    }

    function _getTrackUploaderErrorMessage(err) {
        var message = null;
        if (err.hasOwnProperty('server') && err.server === true) {
            message = _uploaderErrors.server[err.code] || null;
        } else {
            message = _uploaderErrors.client[err.code] || null;
        }
        if (!message) {
            message = _uploaderErrors.server[UPLOAD_FAILED];
        }
        return message;
    }

    function _renderFileExistsControl(assetId) {
        if (_tplAssetFileExists == null) {
            _tplAssetFileExists = kite('#lvdwcaf-tpl-asset-file-exists');
        }
        return _tplAssetFileExists({
            assetId: assetId
        });
    }

    function _renderFileMissingControl(assetId) {
        if (_tplAssetFileMissing == null) {
            _tplAssetFileMissing = kite('#lvdwcaf-tpl-asset-file-missing');
        }
        return _tplAssetFileMissing({
            assetId: assetId
        });
    }

    function _setAssetFileUploaded(assetId) {
        _getAssetContainerElement(assetId)
            .html(_renderFileExistsControl(assetId));
        _destroyAssetUploader(assetId);
    }

    function _setAssetFileRemoved(assetId) {
        _getAssetContainerElement(assetId)
            .html(_renderFileMissingControl(assetId));
        _initUploaderForElement(_getAssetBrowseButtonElement(assetId));
    }

    function _handleUploaderFilesAdded(assetId, uploader, files) {
        if (!files || !files.length) {
            return;
        }

        var file = files[0];
        if (file.size <= 102400) {
            uploader.setOption('chunk_size', Math.round(file.size / 2));
        } else {
            uploader.setOption('chunk_size', 102400);
        }

        uploader.disableBrowse(true);
        uploader.start();
    }

    function _handleUploaderProgress(assetId, uploader, file) {
        if (uploader.state == plupload.STARTED) {
            _showProgress();
        }
    }

    function _handleUploaderError(assetId, uploader, error) {
        uploader.disableBrowse(false);
        uploader.refresh();

        _hideProgress();
        _toastMessage(false, _getTrackUploaderErrorMessage(error));
    }

    function _getChunkUploadResponseStatus(response) {
        var status = UPLOAD_OK;
        if (response != null) {
            try {
                response = JSON.parse(response);
                status = parseInt(response.status || 0);
            } catch (e) {
                status = UPLOAD_FAILED;
            }
        } else {
            status = UPLOAD_FAILED;
        }
        return status;
    }

    function _handleChunkCompleted(assetId, uploader, file, result) {
        var status = _getChunkUploadResponseStatus(result.response || null);
        if (status != UPLOAD_OK) {
            uploader.stop();
            uploader.disableBrowse(false);

            _hideProgress();
            _toastMessage(false, _getTrackUploaderErrorMessage({
                server: true,
                code: status
            }));
        }
    }

    function _handleUploaderCompleted(assetId, uploader, files) {
        uploader.disableBrowse(false);
        uploader.refresh();

        _hideProgress();
        _setAssetFileUploaded(assetId);
        _toastMessage(true, lvdwcmcSettingsL10n.assetUploadOk);
    }

    function _getPaymentAssetUploaderHandlers(assteId) {
        return {
            FilesAdded: function(uploader, files) {
                _handleUploaderFilesAdded(assetId, uploader, files);
            },
            UploadProgress: function(uploader, file) {
                _handleUploaderProgress(assetId, uploader, file);
            },
            UploadComplete: function(uploader, files) {
                _handleUploaderCompleted(assetId, uploader, files);
            },
            ChunkUploaded: function(uploader, file, result) {
                _handleChunkCompleted(assetId, uploader, file, result);
            },
            Error: function(uploader, error) {
                _handleUploaderError(assetId, uploader, error);
            }
        };
    }

    function _createPaymentAssetUploader(browseElementId, assetId, requestParams) {
        return new plupload.Uploader({
            browse_button: browseElementId,
            filters: {
                max_file_size: window.lvdwcmc_uploadMaxFileSize || 10485760,
                mime_types: _paymentAssetFileTypes[assetId] || [],
                prevent_duplicates: true
            },
            runtimes: 'html5,html4',
            multipart: true,
            multipart_params: $.extend(requestParams || {}, {
                assetId: assetId
            }),
            chunk_size: window.lvdwcmc_uploadChunkSize || 102400,
            url: _getAssetFileUploadUrl(),
            multi_selection: false,
            urlstream_upload: true,
            unique_names: false,
            file_data_name: window.lvdwcmc_uploadKey,
            init: _getPaymentAssetUploaderHandlers(assetId)
        });
    }

    function _fileEndsWithExtension(file, extension) {
        var testFileName = file.name.toLowerCase();
        var testExtension = '.' + extension.toLowerCase();
        
        if ($.isFunction(testFileName.endsWith)) {
            return testFileName.endsWith(testExtension);
        } else {
            var indexOfDot = testFileName.indexOf('.');
            return indexOfDot >= 0 
                ? testFileName.substr(indexOfDot) == extension 
                : false;
        }
    }

    function _configureUploaderFilters() {
        plupload.addFileFilter('mime_types', function(allowedMimeTypes, file, readyFn) {
            var isAllowed = false;
            if (allowedMimeTypes.length > 0) {
                for (var iAllowed = 0; iAllowed < allowedMimeTypes.length; iAllowed ++) {
                    var extensions = allowedMimeTypes[iAllowed].extensions.split(',');
                    for (var iExt = 0; iExt < extensions.length; iExt ++) {
                        if (_fileEndsWithExtension(file, extensions[iExt])) {
                            isAllowed = true;
                            break;
                        }
                    }
                }
            } else {
                isAllowed = true;
            }

            if (!isAllowed) {
                this.trigger('Error', {
                    code : plupload.FILE_EXTENSION_ERROR,
                    message : lvdwcmcSettingsL10n.errPluploadFileType,
                    file : file
                });
            }

            readyFn(isAllowed);
        });
    }

    function _initUploaderForElement($element) {
        var browseElementId = $element.attr('id');
        var assetId = $element.attr('data-asset-id');

        var uploader = _createPaymentAssetUploader(browseElementId, 
            assetId, 
            {});

        _initAssetUploader(assetId, uploader);
    }

    function _initAssetFileUploaders() {
        _initUploaderErrorMessages();
        _configureUploaderFilters();

        $('.lvdwcmc-payment-asset-file-selector').each(function() {
            _initUploaderForElement($(this));
        });
    }

    function _removeAsset(assetId) {
        _showProgress();
        $.ajax(_getAssetFileRemovalUrl(), {
            type: 'POST',
            dataType: 'json',
            data: {
                assetId: assetId
            }
        }).done(function(data, status, xhr) {
            _hideProgress();
            if (data) {
                if (data.success) {
                    _setAssetFileRemoved(assetId);
                    _toastMessage(true, lvdwcmcSettingsL10n.assetRemovalOk);
                } else {
                    _toastMessage(false, data.message || lvdwcmcSettingsL10n.errAssetFileCannotBeRemoved);
                }
            } else {
                _toastMessage(false, lvdwcmcSettingsL10n.errAssetFileCannotBeRemoved);
            }
        }).fail(function() {
            _hideProgress();
            _toastMessage(false, lvdwcmcSettingsL10n.errAssetFileCannotBeRemovedNetwork);
        });
    }

    function _initAssetFileRemoval() {
        $('body').on('click', '.lvdwcmc-payment-asset-file-removal', function(e) {
            var assetId = $(this).attr('data-asset-id');
            if (confirm(lvdwcmcSettingsL10n.warnRemoveAssetFile)) {
                _removeAsset(assetId);
            }

            e.preventDefault();
            e.stopPropagation();
        });
    }

    function _initToastMessages() {
        toastr.options = $.extend(toastr.options, {
            target: '#mainform',
            positionClass: 'toast-bottom-right',
            timeOut: 4000
        });
    }

    $(document).ready(function() {
        _initToastMessages();
        _initAssetFileUploaders();
        _initAssetFileRemoval();
    });
})(jQuery);