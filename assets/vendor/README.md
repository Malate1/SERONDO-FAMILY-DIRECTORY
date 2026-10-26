# Frontend libraries

These pinned distributions are served locally from this directory. Keep their license files and the jQuery UI `images` directory when deploying.

| Library | Version | Upstream |
| --- | --- | --- |
| jQuery | 3.7.1 | https://jquery.com/ |
| jQuery UI | 1.14.2 | https://jqueryui.com/ |
| jQuery Timepicker Addon | 1.6.3 | https://trentrichardson.com/examples/timepicker/ |
| SweetAlert2 | 11.26.25 | https://sweetalert2.github.io/ |
| Toastr | 2.1.4 | https://github.com/CodeSeven/toastr |

Load order is defined in `application/views/layout/footer.php`; widget styles are loaded before the app overrides in `layout/header.php`. The timepicker add-on supplies the datetime picker on top of jQuery UI Datepicker.
