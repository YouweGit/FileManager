var Media = function () {
    "use strict";
    var upload_modal,
        ItemsContainer,
        mediaItemsElement,
        mediaDirsElement,
        activePath,
        history_index = [],
        current_index = 0,
        isPopup,
        error_modal,
        preview_modal,
        info_modal,
        rename_origin_name,
        rename_element,
        rename_origin_ext,
        root_dir,
        self = this,
        active_input = false,
        sub_dirs,
        active_dir,
        active_ul,
        active_span,
        selected_item,
        selectors = {
            layout: {
                block : "file_body_block",
                list: "file_body_list"
            },
            containers: {
                modalParent: "body",
                container: "#MediaContainer",
                itemsContainer: "#Items",
                dirsContainer: "#Dirs",
                mediaItemsElement: ".MediaListItems",
                mediaDirsElement: ".MediaListDirs",
                errorContent: "#errorContent",
                previewContent: "#previewContent",
                previewVideo: "#preview_vid",
                uploadScreen: "#uploadLoadingScreen",
                loadingScreen: "#loadingScreen",
                mediaTable: "#media-table-wrapper",
                mediaLoadingScreen: '.MedialoadingScreen'
            },
            modals: {
                error: "#errorModal",
                upload: "#media-upload-dialog",
                info: "#infoModal",
                confirm: "#media-confirm-dialog",
                preview: "#previewModal"
            },
            classes: {
                activeDir: "dir_active",
                mediaItemDir: "yw_media_item_dir",
                mediaDir: "yw_media_dir",
                mediaItem: "yw_media_item",
                toggleDir: "toggleDir",
                emptyMedia: "yw_media_empty",
                previewImage: "preview_img",
                mediaDraggable: "yw_media_drag",
                mediaDirectoryLine: "yw_media_directory_line",
                arrows: {
                    right: 'fa-caret-right',
                    left: 'fa-caret-left',
                    up: 'fa-caret-up',
                    down: 'fa-caret-down'
                },
                folder: 'folder',
                datarow: 'datarow',
                mediaType: 'yw_media_type',
                mediaPage: 'yw_media_page',
                emptyRow: 'empty_row',
                mediaDragging: 'yw_file_dragging',
                blockRow: 'block_row',
                popOver: 'popover-dismiss',
                video: "video",
                selectedItem: "item_selected",
                confirm: "confirm",
                dragHelper: "ui-draggable-helper",
                dropHighlight: "droppable-highlight"
            },
            ids: {
                previewVideo: "preview_vid",
                newDir: "media_newfolder",
                renameItem: "media_rename_file",
                originRenameItem: "media_origin_file_name",
                originRenameExt: "media_origin_file_ext"
            },
            fields: {
                form: "#media_form",
                names: {
                    newDir: "media[newfolder]",
                    renameItem: "media[rename_file]",
                    originRenameItem: "media[origin_file_name]",
                    originRenameExt: "media[origin_file_ext]",
                    file: "media[file]"
                },
                dragAndDrop: "#dragandrophandler",
                newDir: "#media_newfolder",
                renameItem: "#media_rename_file",
                token: "#media__token"
            },
            buttons: {
                select: "#select_btn",
                rename: "#rename_btn",
                extract: "#extract_btn",
                copy: "#copy_btn",
                cut: "#cut_btn",
                paste: "#paste_btn",
                preview: "#preview_btn",
                delete: "#delete_btn",
                back: "#back_btn",
                forward: "#forward_btn",
                upload: "#upload_file_btn",
                download: "#download_btn",
                folder: "#new_folder_btn",
                blockView: "#set_display_block",
                listView: "#set_display_list"
            }
        },
        contextMenu = {
            keys: {
                delete: 'delete',
                info: 'info',
                extract: 'extract',
                rename: 'rename',
                previewVideo: selectors.ids.previewVideo,
                previewImage: selectors.classes.previewImage,
                new_dir: 'new_dir'
            },
            extra_types: {
                zip: "zip",
                image: "image",
                video: "video"
            },
            array_types: [
                "zip",
                "image",
                "video",
                "default",
                "pdf",
                "php",
                "shellscript",
                "code"
            ],
            items: {
                rename: {name: "Rename", icon: "rename"},
                info: {name: "File Information", icon: "fileinfo"},
                delete: {name: "Delete", icon: "delete"},
                sep1: "---------"
            },
            subItems: {
                preview_img: {name: "Preview", icon: "preview"},
                preview_vid: {name: "Preview", icon: "preview"},
                download: {name: "Download", icon: "download"},
                rename: {name: "New Directory", icon: "rename"},
                extract: {name: "Extract", icon: "extract"},
                new_dir: {name: "New Directory", icon: "newdir"}
            }
        },
        routes = {
            delete: "youwe_media_delete",
            list: "youwe_media_list",
            extract: "youwe_media_extract",
            fileInfo: "youwe_media_fileinfo",
            move: "youwe_media_move",
            download: "youwe_media_download",
            paste: "youwe_media_paste",
            copy: "youwe_media_copy"
        },
        messages = {
            errors: {
                fileUpload: "File with this extension is not allowed"
            }
        },

        /**
         * The ajax request for handling the form actions
         * @param {string} url
         * @param {{token: (*|jQuery), dir_path: *}} data
         * @param {string} method
         * @param {bool=true} reloadList
         * @param {bool=true} reloadFileList
         */
        ajaxRequest = function (url, data, method, reloadList, reloadFileList) {
            reloadList = (reloadList === undefined) ? true : reloadList;
            reloadFileList = (reloadFileList === undefined) ? true : reloadFileList;
            $.ajax({
                type: method,
                async: false,
                url: url,
                data: data,
                success: function (data) {
                    if (reloadList === true) {
                        self.reloadDirList();
                    }
                    if (reloadFileList === true) {
                        self.reloadFileList();
                    }
                    return true;
                },
                error: function (xhr) {
                    $(selectors.containers.errorContent).html(xhr.responseText);
                    error_modal.modal({show: true});
                    return false;
                }
            });
        },

        /**
         * Set the popover on the file usages element
         * @param {jQuery} element
         */
        setPopover = function (element) {
            element.popover({
                html: true,
                title: "Usages",
                placement: "left",
                trigger: 'hover'
            });
        },

        /**
         * @param {jQuery} element
         */
        addActiveClass = function (element) {
            $("." + selectors.classes.activeDir).removeClass(selectors.classes.activeDir);
            element.addClass(selectors.classes.activeDir);
        },

        /**
         * displays the loadingScreen
         */
        uploadloadingScreen = function () {
            $(selectors.containers.uploadScreen).toggle();
        },

        /**
         * displays the loadingScreen
         */
        loadingScreen = function () {
            $(selectors.containers.loadingScreen).show();
        },

        /**
         * Get the extension of the file
         * @param {string} filename
         * @returns {string}
         */
        getExt = function (filename) {
            var dot_pos = filename.lastIndexOf(".");
            if (dot_pos === -1) {
                return "";
            }
            return filename.substr(dot_pos + 1).toLowerCase();
        },

        /**
         * Get parameters from the URL
         * @param {string} paramName
         * @returns {*}
         */
        getUrlParam = function (paramName) {
            var reParam = new RegExp('(?:[?&]|&amp;)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);
            return (match && match.length > 1
            ) ? match[1] : '';
        },

        /**
         * Display the input field for renaming the file
         * @param {jQuery} element
         */
        renameFile = function (element) {
            // these variables are defined at start of the file
            var rename_name;
            rename_element = element.find("span");
            rename_origin_name = rename_element.html();
            if (!rename_element.hasClass(selectors.classes.mediaItemDir)) {
                rename_origin_ext = getExt(rename_element.html());
                rename_name = rename_origin_name.replace(/\.[^\/.]+$/, '');
            } else {
                rename_origin_ext = "";
                rename_name = rename_origin_name;
            }
            rename_element.html('<input type="text"   name="' + selectors.fields.names.renameItem + '" id="' + selectors.ids.renameItem + '" value="' +
                rename_name + '">' +
                '<input type="hidden" name="' + selectors.fields.names.originRenameItem + '" id="' + selectors.ids.originRenameItem + '" value="' +
                rename_origin_name + '">' +
                '<input type="hidden" name="' + selectors.fields.names.originRenameExt + '" id="' + selectors.ids.originRenameExt + '" value="' +
                rename_origin_ext + '">');
            active_input = true;
            $(selectors.fields.renameItem).focus();
        },

        /**
         * Download the selected file
         * @param {jQuery} file_element
         */
        downloadFile = function (file_element) {
            var file_name = file_element.find("span").html(),
                dir_path = (activePath !== null ? activePath : ""),
                route = Routing.generate(routes.download, {"path": dir_path+"/"+file_name}),
                data = {
                    token: $(selectors.fields.token).val(),
                    dir_path: activePath,
                    filename: file_name
                };
            window.open(route, '_blank');
            //ajaxRequest(route, data, "POST", false, false);
        },

        /**
         * Set the copied file in the session
         * @param {jQuery} file_element
         * @param {string} type - copy or paste
         */
        copyFile = function (file_element, type) {
            var file_name = file_element.find("span").html(),
                route = Routing.generate(routes.copy, {'type': type}),
                data = {
                    token: $(selectors.fields.token).val(),
                    dir_path: activePath,
                    filename: file_name
                };
            ajaxRequest(route, data, "POST");
        },

        /**
         * Paste the copied/cutted file in the active dir
         */
        pasteFile = function () {
            var route = Routing.generate(routes.paste),
                data = {
                    token: $(selectors.fields.token).val(),
                    dir_path: activePath
                };
            ajaxRequest(route, data, "POST");
        },

        /**
         * Display the folder input field
         */
        addFolder = function () {
            var row = $("." + selectors.classes.emptyMedia);
            row.removeClass("hidden");
            row.find('span').html(
                '<input type="text" name="' + selectors.fields.names.newDir + '" id="' + selectors.ids.newDir + '">'
            );
            active_input = true;
            $(selectors.fields.newDir).focus();
        },

        /**
         * Send ajax request to delete the selected file
         * @param {string} file_name
         */
        deleteFile = function (file_name) {
            var dir_route = Routing.generate(routes.delete),
                data = {
                    token: $(selectors.fields.token).val(),
                    dir_path: activePath,
                    filename: file_name
                };
            ajaxRequest(dir_route, data, "POST");
        },

        /**
         * Confirm box for the delete action
         * @param {string} file_name
         */
        deleteConfirm = function (file_name) {
            var modalHTML = $(selectors.modals.confirm).html(),
                modal = $(modalHTML);

            $(selectors.containers.modalParent).append(modal);
            modal.modal('show');
            modal.find("." + selectors.classes.confirm).click(function () {
                deleteFile(file_name);
            });
        },

        /**
         * Send ajax request to extract the selected zip
         * @param {string} zip_name
         */
        extractZip = function (zip_name) {
            var new_dir_route = Routing.generate(routes.extract),
                data = {
                    token: $(selectors.fields.token).val(),
                    dir_path: activePath,
                    filename: zip_name
                };
            ajaxRequest(new_dir_route, data, "POST");
        },

        /**
         * Check if the form should be submitted for renaming the file or directory
         */
        submitRenameFile = function () {
            var element = $(selectors.fields.renameItem),
                data = $(selectors.fields.form).serialize(),
                route = Routing.generate(routes.list, {"dir_path": activePath});
            if (rename_origin_ext !== "") {
                rename_origin_ext = "." + rename_origin_ext;
            }
            if (element.val() !== "" && element.val() + rename_origin_ext !== rename_origin_name) {
                if (!ajaxRequest(route, data, "POST")) {
                    rename_element.html(rename_origin_name);
                    active_input = false;
                }
            } else {
                rename_element.html(rename_origin_name);
                active_input = false;
            }
        },

        /**
         * Check if the form should be submitted for creating a new folder
         */
        submitNewFolder = function () {
            if ($(selectors.fields.newDir).val() !== "") {
                var data = $(selectors.fields.form).serialize(),
                    route = Routing.generate(routes.list, {"dir_path": activePath});
                if (!ajaxRequest(route, data, "POST")) {
                    $("." + selectors.classes.emptyMedia).addClass("hidden");
                    ItemsContainer.find("." + selectors.classes.mediaDraggable).draggable('enable');
                    active_input = false;
                }
            } else {
                $("." + selectors.classes.emptyMedia).addClass("hidden");
                ItemsContainer.find("." + selectors.classes.mediaDraggable).draggable('enable');
                active_input = false;
            }
        },

        /**
         * Setup the directory list by opening the current directory and the parents.
         */
        directoryListSetup = function () {

            // Prepare the directory list
            $(selectors.containers.container).find(selectors.containers.mediaDirsElement + ' li > ul').each(function () {
                var parent_li = $(this).parent('li'),
                    sub_ul = $(this).remove();
                parent_li.addClass(selectors.classes.folder);

                parent_li.find('.' + selectors.classes.toggleDir).wrapInner('<a>').find('a').click(function () {
                    $(this).find('i').toggleClass(selectors.classes.arrows.down + " " + selectors.classes.arrows.right);
                    //addActiveClass(parent_li);
                    sub_ul.slideToggle();
                    if ($(this).find('i').hasClass(selectors.classes.arrows.right)) {
                        sub_ul.each(function () {
                            $(this).find("ul").slideUp();
                            if ($(this).find("i").hasClass(selectors.classes.arrows.down)) {
                                $(this).find("i").removeClass(selectors.classes.arrows.down);
                                $(this).find("i").addClass(selectors.classes.arrows.right);
                            }
                        });
                    }
                });

                parent_li.append(sub_ul);
            });

            // Display or hide the directory's
            sub_dirs.hide();
            active_ul.show();

            active_dir.parents("ul").show();
            active_dir.parents("li").each(function () {
                $(this).find("span." + selectors.classes.toggleDir + " i:first").removeClass(selectors.classes.arrows.right);
                $(this).find("span." + selectors.classes.toggleDir + " i:first").addClass(selectors.classes.arrows.down);
            });

            active_span.find("span." + selectors.classes.toggleDir + " i").removeClass(selectors.classes.arrows.right);
            active_span.find("span." + selectors.classes.toggleDir + " i").addClass(selectors.classes.arrows.down);
        },

        /**
         * Navigate through directories
         * @param {jQuery} parent_li
         * @param {string} dir_path
         */
        navigateTo = function (parent_li, dir_path) {
            addActiveClass(parent_li);

            current_index += 1;
            var dir_route = Routing.generate(routes.list, {"dir_path": dir_path}),
                history_data = {
                    "path": dir_path,
                    "url": dir_route
                };
            history_index.splice(current_index, (history_index.length - current_index
            ));
            history_index.push(history_data);
            activePath = dir_path;

            loadingScreen();
            parent_li.find('>ul').slideDown();
            self.reloadFileList();
            if (!isPopup) {
                window.history.pushState({url: dir_route}, document.title, dir_route);
            }
        },

        /**
         * Change directory and slide down the selected directory in the directory list
         * @param {jQuery} element
         */
        changeDir = function (element) {
            var dir_path = (activePath !== null ? activePath + "/" : ""
                    ) + element.html(),
                parent_li = $("span[id='" + dir_path + "']").parent("span").parent("li"),
                sub_ul = parent_li.children();

            sub_ul.slideDown();

            parent_li.find("span." + selectors.classes.toggleDir + " i:first").removeClass(selectors.classes.arrows.right);
            parent_li.find("span." + selectors.classes.toggleDir + " i:first").addClass(selectors.classes.arrows.down);

            navigateTo(parent_li, dir_path);
        },

        /**
         * Callback function for CKEditor
         * @param {string} file
         */
        getFileCallback = function (file) {
            var funcNum = getUrlParam('CKEditorFuncNum');
            if (funcNum) {
                window.opener.CKEDITOR.tools.callFunction(funcNum, "/" + file);
            } else {
                window.opener.processFile(file);
            }
            window.close();
        },

        /**
         * Displays the preview of the selected element
         */
        displayPreview = function () {
            var path,
                file_name = selected_item.find("span:first").html();
            if (activePath !== null) {
                path = "/" + root_dir + "/" + activePath + "/";
            } else {
                path = "/" + root_dir + "/";
            }

            $(selectors.containers.previewContent).html("<img src='" + path + file_name + "'/>");
            preview_modal.modal({show: true});
        },

        /**
         * Show the file information
         * @param {jQuery} element
         */
        showInfo = function (element) {
            var filename = element.find("span").html(),
                info_table,
                file_info_route = Routing.generate(routes.fileInfo,
                    {"dir_path": activePath, "filename": filename});

            $.ajax({
                type: "GET",
                async: false,
                url: file_info_route,
                success: function (data) {
                    var json_data = JSON.parse(data);
                    info_table = info_modal.find("table");
                    info_table.find("td." + selectors.classes.datarow).each(function () {
                        $(this).html(json_data[$(this).attr("data-category")]);
                    });
                    info_modal.modal({show: true});
                    return true;
                },
                error: function (xhr) {
                    $(selectors.containers.errorContent).html(xhr.responseText);
                    error_modal.modal({show: true});
                    return false;
                }
            });
        },

        /**
         * Navigate back or forward through the history
         */
        navigateHistory = function () {
            loadingScreen();

            var history_obj = history_index[current_index];
            activePath = history_obj.path;
            setTimeout(function () {
                self.reloadFileList();
                var parent_li = $("span[id='" + history_obj.path + "']").closest("li");
                addActiveClass(parent_li);
            }, 200);
        },

        /**
         * Callback functions when clicking on a context menu item
         * @param {jQuery} element
         * @param {string} key
         */
        contextCallback = function (element, key) {
            var zip_name, file_name, path, preview_html, item_element = element.closest("." + selectors.classes.mediaType);
            if (activePath !== null) {
                path = "/" + root_dir + "/" + activePath + "/";
            } else {
                path = "/" + root_dir + "/";
            }
            if (key === contextMenu.keys.new_dir) {
                addFolder();
            } else if (key === contextMenu.keys.extract) {
                zip_name = item_element.find("span." + selectors.classes.mediaPage + "." + contextMenu.extra_types.zip).html();
                extractZip(zip_name);
            } else if (key === contextMenu.keys.rename) {
                renameFile(item_element);
            } else if (key === contextMenu.keys.info) {
                showInfo(item_element);
            } else if (key === contextMenu.keys.delete) {
                file_name = item_element.find("span").html();
                deleteConfirm(file_name);
            } else if (key === contextMenu.keys.previewImage) {
                file_name = item_element.find("span").html();
                $(selectors.containers.previewContent).html("<img src='" + path + file_name + "'/>");
                preview_modal.modal({show: true});
            } else if (key === contextMenu.keys.previewVideo) {
                file_name = item_element.find("span").html();
                preview_html = "<video id='" + selectors.ids.previewVideo + "' preload='metadata' controls> " +
                    "<source src='" + path + file_name + "' type='video/mp4'></video>";
                $(selectors.containers.previewContent).html(preview_html, function () {
                    $(selectors.containers.previewVideo).load();
                });

                preview_modal.modal({show: true});
            }
        },

        /**
         * Create context menu for the given type
         * @param {string} type
         */
        getContextItems = function (type) {
            var items = $.extend({}, contextMenu.items);

            if (type === contextMenu.extra_types.zip) {
                items.extract = contextMenu.subItems.extract;
            } else if (type === contextMenu.extra_types.image) {
                items.preview_img = contextMenu.subItems.preview_img;
            } else if (type === contextMenu.extra_types.video) {
                items.preview_vid = contextMenu.subItems.preview_vid;
            }
            return items;
        },

        /**
         * Set the context menu for right clicking on a file row
         * Create one for normal files, and one for zip files.
         * @param {string} type
         */
        setContextMenu = function (type) {
            ItemsContainer.contextMenu({
                selector: '.' + selectors.classes.mediaType + '.' + type,
                callback: function (key) {
                    contextCallback($(this), key);
                },
                items: getContextItems(type)
            });

            ItemsContainer.contextMenu({
                selector: '.' + selectors.classes.mediaDir + ":not('.hidden')",
                callback: function (key) {
                    contextCallback($(this), key);
                },
                items: $.extend({}, contextMenu.items)
            });

            ItemsContainer.contextMenu({
                selector: selectors.containers.mediaTable,
                callback: function (key) {
                    contextCallback($(this), key);
                },
                items: {
                    "new_dir": contextMenu.subItems.new_dir
                }
            });
        },

        /**
         * Loop trough the types for creating the context menu.
         * The type should be the class of the row
         */
        createContextMenu = function () {
            var index,
                types = contextMenu.array_types;
            for (index = 0; index < Object.keys(types).length; index += 1) {
                setContextMenu(types[index]);
            }
        },

        /**
         * Setup the drag and drop for moving files
         */
        setFileDrag = function () {
            ItemsContainer.find("." + selectors.classes.mediaDraggable).draggable({
                opacity: 0.9,
                cursor: "move",
                cursorAt: {
                    left: 0,
                    top: 0
                },
                revert: true,
                helper: 'clone',
                start: function (e, ui) {
                    if (e.originalEvent) {
                        $(ui.helper).addClass(selectors.classes.dragHelper);
                        $(this).children().addClass(selectors.classes.mediaDragging);
                    }
                },
                stop: function () {
                    $(this).children().removeClass(selectors.classes.mediaDragging);
                }
            });

            // The move to a dir in the item list
            ItemsContainer.find("." + selectors.classes.mediaDir).droppable({
                hoverClass: selectors.classes.dropHighlight,
                tolerance: "pointer",
                drop: function (event, ui) {
                    var file = ui.draggable,
                        filename = file.find("span").html(),
                        target = $(event.target),
                        target_name = target.find("span").html(),
                        target_file = root_dir + "/" + (activePath !== null ? activePath + "/" : "") + target_name,
                        route = Routing.generate(routes.move),
                        data = {
                            token: $(selectors.fields.token).val(),
                            dir_path: activePath,
                            filename: filename,
                            target_file: target_file
                        };

                    if (filename !== target_name) {
                        ajaxRequest(route, data, "POST");
                    }
                }
            });

            // The move to directory list
            $(selectors.containers.mediaDirsElement + " ul li span." + selectors.classes.mediaDirectoryLine).droppable({
                hoverClass: selectors.classes.dropHighlight,
                tolerance: "pointer",
                drop: function (event, ui) {
                    var file = ui.draggable,
                        filename = file.find("span").html(),
                        target = $(event.target),
                        target_name = target.find("span." + selectors.classes.mediaDir).attr("id"),
                        target_dir,
                        route = Routing.generate(routes.move),
                        data;
                    if (root_dir !== target_name) {
                        target_dir = root_dir + "/" + target_name;
                    } else {
                        target_dir = root_dir;
                    }

                    data = {
                        token: $(selectors.fields.token).val(),
                        dir_path: activePath,
                        filename: filename,
                        target_file: target_dir
                    };

                    if (filename !== target_name) {
                        ajaxRequest(route, data, "POST");
                    }
                }
            });
        },

        /**
         * Define the functions of the dropzone.
         * This has be done with a 'on' because ajax reloads the dom elements
         *
         * @param {Object} obj
         */
        setDropZoneFunctions = function (obj) {

            // Display a error message when the request throws a exception
            obj.on("error", function () {
                $(selectors.containers.errorContent).html(messages.errors.fileUpload);
                error_modal.modal({show: true});
                uploadloadingScreen();
            });

            // Change the url of the upload to place the file in the right directory
            obj.on("processing", function () {
                uploadloadingScreen();
                var new_dir_route;
                new_dir_route = Routing.generate(routes.list, {"dir_path": activePath});
                obj.options.url = new_dir_route;
            });
        },

        /**
         * Bind the dropzone to the drag and drop div
         * @param {string} dir_path
         */
        setDropZone = function (dir_path) {
            var dir_route = Routing.generate(routes.list, {"dir_path": dir_path});
            $(selectors.fields.form).dropzone({
                url: dir_route,
                paramName: selectors.fields.names.file,
                uploadMultiple: true,
                clickable: selectors.fields.dragAndDrop,
                success: function () {
                    upload_modal.modal('hide');
                    self.reloadFileList();
                },
                addedfile: function () {
                    uploadloadingScreen();
                },
                init: function () {
                    setDropZoneFunctions(this);
                }
            });
        },

        disableToolbarItems = function () {
            $(selectors.buttons.download).attr("disabled", "disabled");
            $(selectors.buttons.select).attr("disabled", "disabled");
            $(selectors.buttons.rename).attr("disabled", "disabled");
            $(selectors.buttons.extract).attr("disabled", "disabled");
            $(selectors.buttons.preview).attr("disabled", "disabled");
            $(selectors.buttons.delete).attr("disabled", "disabled");
            $(selectors.buttons.copy).attr("disabled", "disabled");
            $(selectors.buttons.cut).attr("disabled", "disabled");
        },

        enableToolbarItems = function () {
            $(selectors.buttons.select).removeAttr("disabled", "disabled");
            $(selectors.buttons.rename).removeAttr("disabled", "disabled");
            $(selectors.buttons.delete).removeAttr("disabled", "disabled");
            $(selectors.buttons.copy).removeAttr("disabled", "disabled");
            $(selectors.buttons.cut).removeAttr("disabled", "disabled");
            $(selectors.buttons.download).removeAttr("disabled", "disabled");

            if (selected_item.hasClass(selectors.classes.mediaDir)) {
                $(selectors.buttons.download).attr("disabled", "disabled");
                $(selectors.buttons.copy).attr("disabled", "disabled");
                $(selectors.buttons.cut).attr("disabled", "disabled");
            } else {
                $(selectors.buttons.download).removeAttr("disabled", "disabled");
                $(selectors.buttons.copy).removeAttr("disabled", "disabled");
                $(selectors.buttons.cut).removeAttr("disabled", "disabled");
            }

            if (selected_item.hasClass(contextMenu.extra_types.zip)) {
                $(selectors.buttons.extract).removeAttr("disabled", "disabled");
            } else {
                $(selectors.buttons.extract).attr("disabled", "disabled");
            }
            if (selected_item.hasClass(contextMenu.extra_types.image) || selected_item.hasClass(contextMenu.extra_types.video)) {
                $(selectors.buttons.preview).removeAttr("disabled", "disabled");
            } else {
                $(selectors.buttons.preview).attr("disabled", "disabled");
            }
        },

        /**
         * Because the list is refreshed by ajax, we cannot set some functions on the DOM elements.
         */
        events = function () {
            /**
             * When clicking on the dir in the directory list, open the directory in the list
             * and display the files in the media file list.
             */
            $(document).on("click", "span." + selectors.classes.mediaDir, function () {

                var sub_ul = $(this).closest("ul").children("ul"),
                    parent_li = $(this).parent("span").parent("li"),
                    dir_path = $(this).attr('id') !== root_dir ? $(this).attr('id') : null;

                sub_ul.slideDown();

                $(this).parent().find("span." + selectors.classes.toggleDir + " i").removeClass(selectors.classes.arrows.right);
                $(this).parent().find("span." + selectors.classes.toggleDir + " i").addClass(selectors.classes.arrows.down);

                navigateTo(parent_li, dir_path);
            });

            /**
             * When clicking on a dir in the file list, open the directory in the list
             * and display the files in the media file list.
             */
            $(document).on("dblclick", "div." + selectors.classes.blockRow + "." + selectors.classes.mediaDir + ",tr." + selectors.classes.mediaDir, function () {
                if (!active_input) {
                    if ($(this).hasClass("disabled")) {
                        return false;
                    }
                    changeDir($(this).find("span"));
                    return true;
                }
                return false;
            });

            /**
             * When the window is a popup, give the file back to its parent with the right path.
             */
            $(document).on("dblclick", "span." + selectors.classes.mediaPage + ",." + selectors.classes.blockRow + "." + selectors.classes.mediaItem, function () {
                if (isPopup) {
                    var path, url;
                    if (activePath !== null) {
                        path = root_dir + "/" + activePath;
                    } else {
                        path = root_dir;
                    }
                    if ($(this).hasClass(selectors.classes.blockRow)) {
                        url = path + "/" + $(this).find("span").html();
                    } else {
                        url = path + "/" + $(this).html();
                    }
                    getFileCallback(url);
                }
            });

            /**
             * When clicking on a empty row, remove the selected item and disable the actions
             */
            $(document).on("click", selectors.containers.mediaTable + " tr." + selectors.classes.emptyRow, function () {
                selected_item = null;
                $("." + selectors.classes.selectedItem).removeClass(selectors.classes.selectedItem);
                disableToolbarItems();
            });

            /**
             * When clicking on a empty part of the media wrapper, remove the selected item and disable the actions
             */
            $(document).on("click", selectors.containers.mediaTable, function (e) {
                if($(e.target).is(selectors.containers.mediaTable) ) {
                    selected_item = null;
                    $("." + selectors.classes.selectedItem).removeClass(selectors.classes.selectedItem);
                    disableToolbarItems();
                }
            });

            /**
             * When clicking on a file or directory, check which actions should be enabled
             */
            $(document).on("click", selectors.containers.mediaTable + " tr:not('." + selectors.classes.emptyRow + "'), div." + selectors.classes.blockRow, function () {
                $("." + selectors.classes.selectedItem).removeClass(selectors.classes.selectedItem);
                $(this).addClass(selectors.classes.selectedItem);
                selected_item = $(this);
                enableToolbarItems();
            });

            /**
             * Change the display of the files and directories to list or block view
             */
            $(document).on("click", selectors.buttons.listView, function () {
                var new_dir_route = Routing.generate(routes.list, {"dir_path": activePath});
                ajaxRequest(new_dir_route, {'display_type': selectors.layout.list}, "GET", false);
            }).on("click", selectors.buttons.blockView, function () {
                var new_dir_route = Routing.generate(routes.list, {"dir_path": activePath});
                ajaxRequest(new_dir_route, {'display_type': selectors.layout.block}, "GET", false);
            });

            /**
             * When pressing enter, do not submit the form but remove the focus on the input filed.
             */
            $(document).on("keypress", selectors.fields.newDir + "," + selectors.fields.renameItem, function (e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    $(this).blur();
                }
            });

            /**
             * When losing focus on the input fields, submit the form with ajax
             */
            $(document).on("blur", selectors.fields.newDir, function (e) {
                e.preventDefault();
                submitNewFolder();
            }).on("blur", selectors.fields.renameItem, function (e) {
                e.preventDefault();
                submitRenameFile();
            });

            /**
             * The action bar buttons functions
             */
            $(document).on("click", selectors.buttons.back, function () {
                if (current_index !== 0) {
                    window.history.back();
                    current_index -= 1;
                    navigateHistory();
                }
            }).on("click", selectors.buttons.forward, function () {
                if (current_index !== history_index.length) {
                    window.history.forward();
                    current_index += 1;
                    navigateHistory();
                }
            }).on("click", selectors.buttons.folder, function () {
                addFolder();
            }).on("click", selectors.buttons.upload, function () {
                upload_modal.modal({show: true});
            }).on("click", selectors.buttons.download, function () {
                downloadFile(selected_item);
            }).on("click", selectors.buttons.select, function () {
                selected_item.dblclick();
            }).on("click", selectors.buttons.copy, function () {
                copyFile(selected_item, 'copy');
            }).on("click", selectors.buttons.cut, function () {
                copyFile(selected_item, 'cut');
            }).on("click", selectors.buttons.paste, function () {
                pasteFile();
            }).on("click", selectors.buttons.rename, function () {
                renameFile(selected_item);
            }).on("click", selectors.buttons.extract, function () {
                var zip_name = selected_item.find("span." + selectors.classes.mediaPage + "." + contextMenu.extra_types.zip).html();
                extractZip(zip_name);
            }).on("click", selectors.buttons.preview, function () {
                displayPreview();
            }).on("click", selectors.buttons.delete, function () {
                var file_name = selected_item.find("span:first").html();
                deleteConfirm(file_name);
            });

            /**
             * Key functions
             */
            var ctrlDown = false;
            var ctrlKey = 17, vKey = 86, cKey = 67, xKey = 88;

            $(document).keydown(function(e) {
                if (e.keyCode === ctrlKey) {
                    ctrlDown = true;
                }
            }).keyup(function(e) {
                if (e.keyCode === ctrlKey) {
                    ctrlDown = false;
                }
            });
            $(document).keydown(function(e) {
                if (ctrlDown && (e.keyCode === cKey)) {
                    copyFile(selected_item, 'copy');
                }
                if (ctrlDown && (e.keyCode === xKey)) {
                    copyFile(selected_item, 'cut');
                }
                if (ctrlDown && (e.keyCode === vKey)) {
                    pasteFile();
                }
            });

            /** Make the back/forward button work */
            window.addEventListener("popstate", function(e) {
                current_index -= 1;
                navigateHistory();
            });

            /**
             * Disable everything under the loading screen when the loading screen is visible
             */
            $(document).on("click", selectors.containers.mediaLoadingScreen, function () {
                return false;
            });
        },

        /**
         * The setup when the document is loaded
         */
        setup = function () {
            history_index.push({
                "path": activePath,
                "url": Routing.generate(routes.list, {"dir_path": activePath})
            });
            disableToolbarItems();
            var popOverElement = $('.' + selectors.classes.popOver);

            directoryListSetup();
            events();
            createContextMenu();
            setPopover(popOverElement);
            setDropZone(activePath);
            setFileDrag();
        },

        /**
         * Construct the object
         */
        construct = function () {
            var media_container = $(selectors.containers.container);
            sub_dirs = media_container.find(selectors.containers.mediaDirsElement + ' ul ul ul');
            active_dir = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir);
            active_ul = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir + '>ul');
            active_span = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir + '>span');

            /** these are defined in the twig file */
            activePath = current_path;
            root_dir = root_folder;
            isPopup = is_popup;

            if (activePath === "") {
                activePath = null;
            }

            ItemsContainer = $(selectors.containers.itemsContainer);
            mediaItemsElement = $(selectors.containers.mediaItemsElement);
            mediaDirsElement = $(selectors.containers.mediaDirsElement);

            setup();

            error_modal = $(selectors.modals.error).modal({show: false});
            preview_modal = $(selectors.modals.preview).modal({show: false});
            upload_modal = $(selectors.modals.upload).modal({show: false});
            info_modal = $(selectors.modals.info).modal({show: false});
            mediaDirsElement.resizable({
                maxWidth: 350,
                minWidth: 125,
                handles: 'e, w'
            });
        };
    /**
     * Reload the file list
     */
    this.reloadFileList = function () {
        loadingScreen();
        var new_dir_route = Routing.generate(routes.list, {"dir_path": activePath});
        mediaItemsElement.load(new_dir_route + " " + selectors.containers.itemsContainer, function () {
            ItemsContainer = $(selectors.containers.itemsContainer);
            var popOverElement = $('.' + selectors.classes.popOver);
            setPopover(popOverElement);
            createContextMenu();
            setFileDrag();
            if (current_index === 0) {
                $(selectors.buttons.back).attr("disabled", "disabled");
            }
            if ((current_index + 1) === history_index.length) {
                $(selectors.buttons.forward).attr("disabled", "disabled");
            }
            disableToolbarItems();
        });
    };

    /**
     * Reload the directory list
     */
    this.reloadDirList = function () {
        var open_dirs_ids = [],
            array_index,
            new_dir_route = Routing.generate(routes.list, {"dir_path": activePath});

        $(selectors.containers.dirsContainer).find(selectors.classes.arrows.down).each(function () {
            open_dirs_ids.push($(this).closest("." + selectors.classes.mediaDirectoryLine).find("span." + selectors.classes.mediaDir).attr("id"));
        });

        mediaDirsElement.load(new_dir_route + " " + selectors.containers.dirsContainer, function () {

            $(selectors.containers.dirsContainer).find("li").find("ul").hide();

            var media_container = $(selectors.containers.container),
                element,
                sub_ul,
                parent_li,
                dir_path;

            sub_dirs = media_container.find(selectors.containers.mediaDirsElement + ' ul ul ul');
            active_dir = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir);
            active_ul = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir + '>ul');
            active_span = media_container.find(selectors.containers.mediaDirsElement + ' li.' + selectors.classes.activeDir + '>span');
            directoryListSetup();
            for (array_index = 0; array_index < open_dirs_ids.length; array_index += 1) {
                element = $("span[id='" + open_dirs_ids[array_index] + "']");
                sub_ul = element.closest("ul").children();
                parent_li = element.closest("span").closest("li");
                dir_path = element.attr('id') !== root_dir ? element.attr('id') : null;

                sub_ul.show();

                element.parent().find("span." + selectors.classes.toggleDir + " i").removeClass(selectors.classes.arrows.right);
                element.parent().find("span." + selectors.classes.toggleDir + " i").addClass(selectors.classes.arrows.down);
            }
            setFileDrag();
        });
    };

    construct();
};

$(function () {
    'use strict';
    var MediaObject;
    MediaObject = new Media();
    MediaObject.reloadDirList();
});