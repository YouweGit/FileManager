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

        /**
         * The ajax request for handling the form actions
         * @param url
         * @param data
         * @param method
         */
        ajaxRequest = function (url, data, method) {
            $.ajax({
                type: method,
                async: false,
                url: url,
                data: data,
                success: function () {
                    self.reloadFileList();
                    self.reloadDirList();
                    return true;
                },
                error: function (xhr) {
                    $('#errorContent').html(xhr.responseText);
                    error_modal.modal({show: true});
                    return false;
                }
            });
        },

        /**
         * Set the popover on the file usages element
         * @param element
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
         * @param element
         */
        addActiveClass = function (element) {
            $(".dir_active").removeClass("dir_active");
            element.addClass("dir_active");
        },

        /**
         * displays the loadingScreen
         */
        uploadloadingScreen = function () {
            $("#uploadloadingScreen").toggle();
        },

        /**
         * displays the loadingScreen
         */
        loadingScreen = function () {
            $("#loadingScreen").show();
        },

        /**
         * Get the extension of the file
         * @param filename
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
         * @param paramName
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
         * @param element
         */
        renameFile = function (element) {
            // these variables are defined at start of the file
            var rename_name;
            rename_element = element.find("span");
            rename_origin_name = rename_element.html();
            if (!rename_element.hasClass("yw_media_item_dir")) {
                rename_origin_ext = getExt(rename_element.html());
                rename_name = rename_origin_name.replace(/\.[^\/.]+$/, '');
            } else {
                rename_origin_ext = "";
                rename_name = rename_origin_name;
            }
            rename_element.html('<input type="text"   name="media[rename_file]" id="media_rename_file" value="' +
                rename_name + '">' +
                '<input type="hidden" name="media[origin_file_name]" id="media_origin_file_name" value="' +
                rename_origin_name + '">' +
                '<input type="hidden" name="media[origin_file_ext]" id="media_origin_file_ext" value="' +
                rename_origin_ext + '">');
            active_input = true;
            $("#media_rename_file").focus();
        },

        /**
         * Display the folder input field
         */
        addFolder = function () {
            var row = $(".yw_media_empty");
            row.removeClass("hidden");
            row.find('span').html(
                '<input type="text" name="media[newfolder]" id="media_newfolder">'
            );
            active_input = true;
            $("#media_newfolder").focus();
        },

        /**
         * Send ajax request to delete the selected file
         * @param file_name
         */
        deleteFile = function (file_name) {
            var dir_route = Routing.generate('youwe_media_delete'),
                data = {
                    token: $("#media__token").val(),
                    dir_path: activePath,
                    filename: file_name
                };
            ajaxRequest(dir_route, data, "POST");
        },

        /**
         * Confirm box for the delete action
         * @param file_name
         */
        deleteConfirm = function (file_name) {
            var modalHTML = $("#media-confirm-dialog").html(),
                modal = $(modalHTML);

            $("body").append(modal);
            modal.modal('show');
            modal.find(".confirm").click(function () {
                deleteFile(file_name);
            });
        },

        /**
         * Send ajax request to extract the selected zip
         * @param zip_element
         */
        extractZip = function (zip_element) {
            var new_dir_route = Routing.generate('youwe_media_extract'),
                data = {
                    token: $("#media__token").val(),
                    dir_path: activePath,
                    zip_name: zip_element
                };
            ajaxRequest(new_dir_route, data, "POST");
        },

        /**
         * Check if the form should be submitted for renaming the file or directory
         */
        submitRenameFile = function () {
            var element = $("#media_rename_file"),
                data = $("#media_form").serialize(),
                route = Routing.generate('youwe_media_list', {"dir_path": activePath});
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
            if ($("#media_newfolder").val() !== "") {
                var data = $("#media_form").serialize(),
                    route = Routing.generate('youwe_media_list', {"dir_path": activePath});
                if (!ajaxRequest(route, data, "POST")) {
                    $(".yw_media_empty").addClass("hidden");
                    ItemsContainer.find(".yw_media_drag").draggable('enable');
                    active_input = false;
                }
            } else {
                $(".yw_media_empty").addClass("hidden");
                ItemsContainer.find(".yw_media_drag").draggable('enable');
                active_input = false;
            }
        },

        /**
         * Setup the directory list by opening the current directory and the parents.
         */
        directoryListSetup = function () {

            // Prepare the directory list
            $('#MediaContainer').find('.MediaListDirs li > ul').each(function () {
                var parent_li = $(this).parent('li'),
                    sub_ul = $(this).remove();
                parent_li.addClass('folder');

                parent_li.find('.toggleDir').wrapInner('<a>').find('a').click(function () {
                    $(this).find('i').toggleClass("fa-caret-down fa-caret-right");
                    addActiveClass(parent_li);
                    sub_ul.slideToggle();
                    if ($(this).find('i').hasClass("fa-caret-right")) {
                        sub_ul.each(function () {
                            $(this).find("ul").slideUp();
                            if ($(this).find("i").hasClass("fa-caret-down")) {
                                $(this).find("i").removeClass("fa-caret-down");
                                $(this).find("i").addClass("fa-caret-right");
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
                $(this).find("span.toggleDir i:first").removeClass("fa-caret-right");
                $(this).find("span.toggleDir i:first").addClass("fa-caret-down");
            });

            active_span.find("span.toggleDir i").removeClass("fa-caret-right");
            active_span.find("span.toggleDir i").addClass("fa-caret-down");
        },

        /**
         * Navigate through directories
         * @param parent_li
         * @param dir_path
         */
        navigateTo = function (parent_li, dir_path) {
            addActiveClass(parent_li);

            current_index += 1;
            var dir_route = Routing.generate('youwe_media_list', {"dir_path": dir_path}),
                history_data = {
                    "path": dir_path,
                    "url": dir_route
                };
            history_index.splice(current_index, (history_index.length - current_index
            ));
            history_index.push(history_data);
            activePath = dir_path;

            loadingScreen();
            self.reloadFileList();
            if (!isPopup) {
                window.history.pushState({url: dir_route}, document.title, dir_route);
            }
        },

        /**
         * Change directory and slide down the selected directory in the directory list
         * @param element
         */
        changeDir = function (element) {
            var dir_path = (activePath !== null ? activePath + "/" : ""
                    ) + element.html(),
                parent_li = $("span[id='" + dir_path + "']").parent("span").parent("li"),
                sub_ul = parent_li.children("ul");

            sub_ul.slideDown();

            parent_li.find("span.toggleDir i:first").removeClass("fa-caret-right");
            parent_li.find("span.toggleDir i:first").addClass("fa-caret-down");

            navigateTo(parent_li, dir_path);
        },

        /**
         * Callback function for CKEditor
         * @param file
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
         * Set the preview for each image
         */
        setPreview = function () {
            $(".block_holder>div.image").each(function () {
                var imagename = $(this).parent().find("span").html();
                $(this).html("<img src='/" + root_dir + "/" +
                    (activePath !== null ? activePath + "/" : "") + imagename + "' alt='preview' class='preview_img'>");
            });
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

            $('#previewContent').html("<img src='" + path + file_name + "'/>");
            preview_modal.modal({show: true});
        },

        /**
         * Show the file information
         * @param element
         */
        showInfo = function (element) {
            var filename = element.find("span").html(),
                info_table,
                file_info_route = Routing.generate('youwe_media_fileinfo',
                    {"dir_path": activePath, "filename": filename});

            $.ajax({
                type: "GET",
                async: false,
                url: file_info_route,
                success: function (data) {
                    var json_data = JSON.parse(data);
                    info_table = info_modal.find("table");
                    info_table.find("td.datarow").each(function () {
                        $(this).html(json_data[$(this).attr("data-category")]);
                    });
                    info_modal.modal({show: true});
                    return true;
                },
                error: function (xhr) {
                    $('#errorContent').html(xhr.responseText);
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
         * @param element
         * @param key
         */
        contextCallback = function (element, key) {

            var zip_name, file_name, path, preview_html, item_element = element.closest(".yw_media_type");
            if (activePath !== null) {
                path = "/" + root_dir + "/" + activePath + "/";
            } else {
                path = "/" + root_dir + "/";
            }
            if (key === 'extract') {
                zip_name = item_element.find("span.yw_media_page.zip").html();
                extractZip(zip_name);
            }
            if (key === 'rename') {
                renameFile(item_element);
            }
            if (key === 'info') {
                showInfo(item_element);
            }
            if (key === 'delete') {
                file_name = item_element.find("span").html();
                deleteConfirm(file_name);
            }
            if (key === 'preview_img') {
                file_name = item_element.find("span").html();
                $('#previewContent').html("<img src='" + path + file_name + "'/>");
                preview_modal.modal({show: true});
            }
            if (key === 'preview_vid') {
                file_name = item_element.find("span").html();
                preview_html = "<video id='preview_vid' preload='metadata' controls> " +
                    "<source src='" + path + file_name + "' type='video/mp4'></video>";
                $('#previewContent').html(preview_html, function () {
                    $("#preview_vid").load();
                });

                preview_modal.modal({show: true});
            }
        },

        /**
         * Create context menu for the given type
         * @param type
         */
        getContextItems = function (type) {
            var items = {
                "rename": {name: "Rename", icon: "rename"},
                "info": {name: "File Information", icon: "fileinfo"},
                "delete": {name: "Delete", icon: "delete"},
                "sep1": "---------"
            };

            if (type === "zip") {
                items.extract = {name: "Extract", icon: "extract"};
            } else if (type === "image") {
                items.preview_img = {name: "Preview", icon: "preview"};
            } else if (type === "video") {
                items.preview_vid = {name: "Preview", icon: "preview"};
            }

            return items;
        },

        /**
         * Set the context menu for right clicking on a file row
         * Create one for normal files, and one for zip files.
         * @param type
         */
        setContextMenu = function (type) {
            ItemsContainer.contextMenu({
                selector: '.yw_media_type .' + type,
                callback: function (key) {
                    contextCallback($(this), key);
                },
                items: getContextItems(type)
            });

            ItemsContainer.contextMenu({
                selector: '.empty_row',
                callback: function (key) {
                    contextCallback($(this), key);
                },
                items: {
                    "rename": {name: "New Directory", icon: "newdir"}
                }
            });
        },

        /**
         * Loop trough the types for creating the context menu.
         * The type should be the class of the row
         */
        createContextMenu = function () {
            var index,
                types = ["image", "zip", "default", "pdf", "video", "php", "shellscript", "code"];

            for (index = 0; index < types.length; index += 1) {
                setContextMenu(types[index]);
            }
        },

        /**
         * Setup the drag and drop for moving files
         */
        setFileDrag = function () {
            ItemsContainer.find(".yw_media_drag").draggable({
                opacity: 0.9,
                cursor: "move",
                cursorAt: {
                    left: 0,
                    top: 0
                },
                revert: true,
                helper: 'clone',
                start: function (e, ui) {
                    $(ui.helper).addClass("ui-draggable-helper");
                    $(this).children().addClass("yw_file_dragging");
                },
                stop: function () {
                    $(this).children().removeClass("yw_file_dragging");
                }
            });

            // The move to a dir in the item list
            ItemsContainer.find(".yw_media_dir").droppable({
                hoverClass: "droppable-highlight",
                tolerance: "pointer",
                drop: function (event, ui) {
                    var file = ui.draggable,
                        filename = file.find("span").html(),
                        target = $(event.target),
                        target_name = target.find("span").html(),
                        target_file = root_dir + "/" + (activePath !== null ? activePath + "/" : "") + target_name,
                        route = Routing.generate('youwe_media_move'),
                        data = {
                            token: $("#media__token").val(),
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
            $("div.MediaListDirs ul li span.yw_media_directory_line").droppable({
                hoverClass: "droppable-highlight",
                tolerance: "pointer",
                drop: function (event, ui) {
                    var file = ui.draggable,
                        filename = file.find("span").html(),
                        target = $(event.target),
                        target_name = target.find("span.yw_media_dir").attr("id"),
                        target_dir,
                        route = Routing.generate('youwe_media_move'),
                        data;
                    if (root_dir !== target_name) {
                        target_dir = root_dir + "/" + target_name;
                    } else {
                        target_dir = root_dir;
                    }

                    data = {
                        token: $("#media__token").val(),
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
         * @param obj
         */
        setDropZoneFunctions = function (obj) {

            // Display a error message when the request throws a exception
            obj.on("error", function () {
                $('#errorContent').html("File with this extension is not allowed");
                error_modal.modal({show: true});
                uploadloadingScreen();
            });

            // Change the url of the upload to place the file in the right directory
            obj.on("processing", function () {
                uploadloadingScreen();
                var new_dir_route;
                new_dir_route = Routing.generate('youwe_media_list', {"dir_path": activePath});
                obj.options.url = new_dir_route;
            });
        },

        /**
         * Bind the dropzone to the drag and drop div
         * @param dir_path
         */
        setDropZone = function (dir_path) {
            var dir_route = Routing.generate('youwe_media_list', {"dir_path": dir_path});
            $("#media_form").dropzone({
                url: dir_route,
                paramName: "media[file]",
                uploadMultiple: true,
                clickable: "#dragandrophandler",
                success: function () {
                    upload_modal.modal('hide');
                    self.reloadFileList();
                    uploadloadingScreen();
                },
                addedfile: function () {

                },
                init: function () {
                    setDropZoneFunctions(this);
                }
            });
        },

        /**
         * Because the list is refreshed by ajax, we cannot set some functions on the DOM elements.
         */
        events = function () {
            /**
             * When clicking on the dir in the directory list, open the directory in the list
             * and display the files in the media file list.
             */
            $(document).on("click", "span.yw_media_dir", function () {

                var sub_ul = $(this).closest("ul").children("ul"),
                    parent_li = $(this).parent("span").parent("li"),
                    dir_path = $(this).attr('id') !== root_dir ? $(this).attr('id') : null;

                sub_ul.slideDown();

                $(this).parent().find("span.toggleDir i").removeClass("fa-caret-right");
                $(this).parent().find("span.toggleDir i").addClass("fa-caret-down");

                navigateTo(parent_li, dir_path);
            });

            /**
             * When clicking on a dir in the file list, open the directory in the list
             * and display the files in the media file list.
             */
            $(document).on("dblclick", "div.block_row.yw_media_dir,tr.yw_media_dir", function () {
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
            $(document).on("dblclick", "span.yw_media_page,.block_row.yw_media_item", function () {
                if (isPopup) {
                    var path, url;
                    if (activePath !== null) {
                        path = root_dir + "/" + activePath;
                    } else {
                        path = root_dir;
                    }
                    if ($(this).hasClass("block_row")) {
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
            $(document).on("click", "#media-table-wrapper tr.empty_row", function () {
                selected_item = null;

                $("#select_btn").attr("disabled", "disabled");
                $("#rename_btn").attr("disabled", "disabled");
                $("#extract_btn").attr("disabled", "disabled");
                $("#preview_btn").attr("disabled", "disabled");
                $("#delete_btn").attr("disabled", "disabled");
            });

            /**
             * When clicking on a file or directory, check which actions should be enabled
             */
            $(document).on("click", "#media-table-wrapper tr:not('.empty_row'), div.block_row", function () {
                $(".item_selected").removeClass("item_selected");
                $(this).addClass("item_selected");
                selected_item = $(this);

                $("#select_btn").removeAttr("disabled", "disabled");
                $("#rename_btn").removeAttr("disabled", "disabled");
                $("#delete_btn").removeAttr("disabled", "disabled");

                if (selected_item.hasClass("zip")) {
                    $("#extract_btn").removeAttr("disabled", "disabled");
                } else {
                    $("#extract_btn").attr("disabled", "disabled");
                }
                if (selected_item.hasClass("image") || selected_item.hasClass("video")) {
                    $("#preview_btn").removeAttr("disabled", "disabled");
                } else {
                    $("#preview_btn").attr("disabled", "disabled");
                }
            });

            /**
             * Change the display of the files and directories to list or block view
             */
            $(document).on("click", "#set_display_list", function () {
                var new_dir_route = Routing.generate('youwe_media_list', {"dir_path": activePath});
                ajaxRequest(new_dir_route, {'display_type': "file_body_list"}, "GET");
            }).on("click", "#set_display_block", function () {
                var new_dir_route = Routing.generate('youwe_media_list', {"dir_path": activePath});
                ajaxRequest(new_dir_route, {'display_type': "file_body_block"}, "GET");
            });

            /**
             * When pressing enter, do not submit the form but remove the focus on the input filed.
             */
            $(document).on("keypress", "#media_newfolder,#media_rename_file", function (e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    $(this).blur();
                }
            });

            /**
             * When losing focus on the input fields, submit the form with ajax
             */
            $(document).on("blur", "#media_newfolder", function (e) {
                e.preventDefault();
                submitNewFolder();
            }).on("blur", "#media_rename_file", function (e) {
                e.preventDefault();
                submitRenameFile();
            });

            /**
             * The action bar buttons functions
             */
            $(document).on("click", "#back_btn", function () {
                if (current_index !== 0) {
                    window.history.back();
                    current_index -= 1;
                    navigateHistory();
                }
            }).on("click", "#forward_btn", function () {
                if (current_index !== history_index.length) {
                    window.history.forward();
                    current_index += 1;
                    navigateHistory();
                }
            }).on("click", "#new_folder_btn", function () {
                addFolder();
            }).on("click", "#upload_file_btn", function () {
                upload_modal.modal({show: true});
            }).on("click", "#select_btn", function () {
                selected_item.dblclick();
            }).on("click", "#rename_btn", function () {
                renameFile(selected_item);
            }).on("click", "#extract_btn", function () {
                var zip_name = selected_item.find("span.yw_media_page.zip").html();
                extractZip(zip_name);
            }).on("click", "#preview_btn", function () {
                displayPreview();
            }).on("click", "#delete_btn", function () {
                var file_name = selected_item.find("span:first").html();
                deleteConfirm(file_name);
            });

            /**
             * Disable everything under the loading screen when the loading screen is visible
             */
            $(document).on("click", ".MedialoadingScreen", function () {
                return false;
            });
        },

        /**
         * The setup when the document is loaded
         */
        setup = function () {
            history_index.push({
                "path": activePath,
                "url": Routing.generate('youwe_media_list', {"dir_path": activePath})
            });
            $("#forward_btn").attr("disabled", "disabled");
            $("#back_btn").attr("disabled", "disabled");
            $("#select_btn").attr("disabled", "disabled");
            $("#rename_btn").attr("disabled", "disabled");
            $("#extract_btn").attr("disabled", "disabled");
            $("#preview_btn").attr("disabled", "disabled");
            $("#delete_btn").attr("disabled", "disabled");

            var popOverElement = $('.popover-dismiss');

            directoryListSetup();
            events();
            createContextMenu();
            setPopover(popOverElement);
            setDropZone(activePath);
            setFileDrag();
            setPreview();
        },

        /**
         * Construct the object
         */
        construct = function () {
            var media_container = $("#MediaContainer");
            sub_dirs = media_container.find('.MediaListDirs ul ul ul');
            active_dir = media_container.find('.MediaListDirs li.dir_active');
            active_ul = media_container.find('.MediaListDirs li.dir_active>ul');
            active_span = media_container.find('.MediaListDirs li.dir_active>span');

            /** these are defined in the twig file */
            activePath = current_path;
            root_dir = root_folder;
            isPopup = is_popup;

            if (activePath === "") {
                activePath = null;
            }

            ItemsContainer = $("#Items");
            mediaItemsElement = $(".MediaListItems");
            mediaDirsElement = $(".MediaListDirs");

            setup();

            error_modal = $('#errorModal').modal({show: false});
            preview_modal = $('#previewModal').modal({show: false});
            upload_modal = $('#media-upload-dialog').modal({show: false});
            info_modal = $('#infoModal').modal({show: false});
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
        var new_dir_route = Routing.generate('youwe_media_list', {"dir_path": activePath});
        mediaItemsElement.load(new_dir_route + " #Items", function () {
            ItemsContainer = $("#Items");
            var popOverElement = $('.popover-dismiss');
            setPopover(popOverElement);
            createContextMenu();
            setFileDrag();
            setPreview();
            if (current_index === 0) {
                $("#back_btn").attr("disabled", "disabled");
            }
            if ((current_index + 1) === history_index.length) {
                $("#forward_btn").attr("disabled", "disabled");
            }
            $("#select_btn").attr("disabled", "disabled");
            $("#rename_btn").attr("disabled", "disabled");
            $("#extract_btn").attr("disabled", "disabled");
            $("#preview_btn").attr("disabled", "disabled");
            $("#delete_btn").attr("disabled", "disabled");
        });
    };

    /**
     * Reload the directory list
     */
    this.reloadDirList = function () {
        var open_dirs_ids = [],
            array_index,
            new_dir_route = Routing.generate('youwe_media_list', {"dir_path": activePath});

        $("#Dirs").find(".fa-caret-down").each(function () {
            open_dirs_ids.push($(this).closest(".yw_media_directory_line").find("span.yw_media_dir").attr("id"));
        });

        mediaDirsElement.load(new_dir_route + " #Dirs", function () {

            $("#Dirs").find("li").find("ul").hide();

            var media_container = $("#MediaContainer"),
                element,
                sub_ul,
                parent_li,
                dir_path;

            sub_dirs = media_container.find('.MediaListDirs ul ul ul');
            active_dir = media_container.find('.MediaListDirs li.dir_active');
            active_ul = media_container.find('.MediaListDirs li.dir_active>ul');
            active_span = media_container.find('.MediaListDirs li.dir_active>span');
            directoryListSetup();
            for (array_index = 0, parent_li, dir_path; array_index < open_dirs_ids.length; array_index += 1) {
                element = $("span[id='" + open_dirs_ids[array_index] + "']");
                sub_ul = element.closest("ul").children();
                parent_li = element.parent("span").parent("li");
                dir_path = element.attr('id') !== root_dir ? element.attr('id') : null;

                sub_ul.show();

                element.parent().find("span.toggleDir i").removeClass("fa-caret-right");
                element.parent().find("span.toggleDir i").addClass("fa-caret-down");
            }
            setFileDrag();
        });
    };

    construct();
};

$(function () {
    'use strict';
    var MediaObject = new Media();
});