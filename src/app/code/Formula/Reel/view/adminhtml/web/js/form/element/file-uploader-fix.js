define(["jquery", "Magento_Ui/js/form/element/file-uploader"], function (
    $,
    FileUploader
) {
    "use strict";

    return FileUploader.extend({
        /**
         * Format file size to human-readable format
         *
         * @param {Number} bytes
         * @returns {String}
         */
        formatSize: function (bytes) {
            if (isNaN(bytes)) {
                return "";
            }

            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + " MB";
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + " KB";
            }

            return bytes + " B";
        },

        /**
         * Override to fix validation
         */
        onBeforeFileUpload: function (e, data) {
            // Set validation state to valid when a file is selected
            this.validation["required-entry"] = false;
            this.valid = true;
            this.error("");

            this._super(e, data);
        },

        /**
         * Override to improve handling of initial file
         */
        initUploader: function () {
            this._super();

            // If we already have a value, mark as valid
            if (this.value() && this.value().length) {
                this.validation["required-entry"] = false;
                this.valid = true;
                this.error("");

                // Fix for existing files that don't have size information
                this.value().forEach(function (file, index) {
                    // If file size is not defined or invalid, try to get it from the server
                    if (!file.size || isNaN(file.size)) {
                        this.getVideoSize(file, index);
                    }
                }, this);
            }
        },

        /**
         * Get video file size from server
         *
         * @param {Object} file
         * @param {Number} index
         */
        getVideoSize: function (file, index) {
            var self = this;

            // Check if we can extract size from file name
            if (file.name) {
                $.ajax({
                    url: this.uploaderConfig.url.replace(
                        "upload",
                        "getFileSize"
                    ),
                    type: "GET",
                    data: {
                        filename: file.name,
                    },
                    success: function (response) {
                        if (response && response.success && response.size) {
                            // Update file size in the value
                            var fileObj = self.value()[index];
                            fileObj.size = response.size;

                            // Force component update
                            self.value.notifySubscribers(self.value());
                        }
                    },
                });
            }
        },
    });
});
