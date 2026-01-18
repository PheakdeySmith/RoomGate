<!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                @php
                                    $companyName = $appSettings->company_name ?: ($appSettings->app_name ?: 'RoomGate');
                                    $footerLogo = $appSettings->footer_logo_path ? asset($appSettings->footer_logo_path) : null;
                                @endphp
                                <div class="text-body d-flex align-items-center gap-2">
                                    @if ($footerLogo)
                                        <img src="{{ $footerLogo }}" alt="{{ $companyName }}" style="height: 20px;">
                                    @endif
                                    <span>
                                        &#169;
                                        <script>
                                            document.write(new Date().getFullYear());
                                        </script>
                                        {{ $companyName }}
                                    </span>
                                </div>
                                <div class="d-none d-lg-inline-block">
                                    <a href="https://themeforest.net/licenses/standard" class="footer-link me-4"
                                        target="_blank">License</a>
                                    <a href="https://themeforest.net/user/pixinvent/portfolio" target="_blank"
                                        class="footer-link me-4">More Themes</a>

                                    <a href="https://demos.pixinvent.com/vuexy-html-Core-template/documentation/"
                                        target="_blank" class="footer-link me-4">Documentation</a>

                                    <a href="https://pixinvent.ticksy.com/" target="_blank"
                                        class="footer-link d-none d-sm-inline-block">Support</a>
                                </div>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->
