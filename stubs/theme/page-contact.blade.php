{{-- Contact Page Template --}}
<x-theme::layouts.main>
    <x-slot:main>
        {{-- Page Header with Electric Storm --}}
        <section class="relative overflow-hidden bg-gradient-to-br from-black via-emerald-950 to-slate-950 py-16 text-white">
            {{-- Electric Orbs --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-20 top-20 h-96 w-96 rounded-full bg-gradient-to-r from-lime-500/20 to-emerald-500/20 blur-3xl"></div>
                <div class="absolute -right-20 bottom-20 h-96 w-96 rounded-full bg-gradient-to-r from-cyan-500/20 to-lime-500/20 blur-3xl"></div>
            </div>

            <div class="container relative mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <h1 class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.5)] md:text-5xl">Get in Touch</h1>

                    <p class="text-xl text-emerald-200/90">
                        We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                    </p>
                </div>
            </div>
        </section>

        {{-- Contact Form & Info Section --}}
        <section class="bg-slate-950 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-6xl">
                    <div class="grid gap-12 lg:grid-cols-2">
                        {{-- Contact Form --}}
                        <div>
                            <h2 class="mb-6 bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-2xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(163,230,53,0.4)]">Send us a message</h2>

                            <p class="mb-8 text-emerald-200/70">
                                Fill out the form below and our team will get back to you within 24 hours.
                            </p>

                            {{-- This would typically be a Livewire or custom form component --}}
                            <form class="space-y-6">
                                <div>
                                    <label for="name" class="mb-2 block text-sm font-medium text-lime-400/90">
                                        Full Name
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        class="w-full rounded-lg border border-emerald-500/30 bg-slate-900 px-4 py-2 text-emerald-100 shadow-[0_0_10px_rgba(163,230,53,0.1)] transition-all duration-300 placeholder:text-emerald-200/40 focus:border-lime-400/50 focus:shadow-[0_0_20px_rgba(163,230,53,0.3)] focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                        required
                                    />
                                </div>

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-medium text-lime-400/90">
                                        Email Address
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="w-full rounded-lg border border-emerald-500/30 bg-slate-900 px-4 py-2 text-emerald-100 shadow-[0_0_10px_rgba(163,230,53,0.1)] transition-all duration-300 placeholder:text-emerald-200/40 focus:border-lime-400/50 focus:shadow-[0_0_20px_rgba(163,230,53,0.3)] focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                        required
                                    />
                                </div>

                                <div>
                                    <label for="subject" class="mb-2 block text-sm font-medium text-lime-400/90">
                                        Subject
                                    </label>
                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        class="w-full rounded-lg border border-emerald-500/30 bg-slate-900 px-4 py-2 text-emerald-100 shadow-[0_0_10px_rgba(163,230,53,0.1)] transition-all duration-300 placeholder:text-emerald-200/40 focus:border-lime-400/50 focus:shadow-[0_0_20px_rgba(163,230,53,0.3)] focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                        required
                                    />
                                </div>

                                <div>
                                    <label for="message" class="mb-2 block text-sm font-medium text-lime-400/90">
                                        Message
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="5"
                                        class="w-full rounded-lg border border-emerald-500/30 bg-slate-900 px-4 py-2 text-emerald-100 shadow-[0_0_10px_rgba(163,230,53,0.1)] transition-all duration-300 placeholder:text-emerald-200/40 focus:border-lime-400/50 focus:shadow-[0_0_20px_rgba(163,230,53,0.3)] focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                        required
                                    ></textarea>
                                </div>

                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-gradient-to-r from-lime-400 to-emerald-500 px-6 py-3 font-semibold text-slate-900 shadow-[0_0_20px_rgba(163,230,53,0.5)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_30px_rgba(163,230,53,0.8)]"
                                >
                                    Send Message
                                </button>
                            </form>
                        </div>

                        {{-- Contact Information --}}
                        <div>
                            <h2 class="mb-6 bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-2xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(52,211,153,0.4)]">Contact Information</h2>

                            <p class="mb-8 text-emerald-200/70">
                                You can also reach us through any of the following methods.
                            </p>

                            <div class="space-y-6">
                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-lime-500/20 to-emerald-500/20 ring-2 ring-emerald-400/30 shadow-[0_0_15px_rgba(163,230,53,0.2)]">
                                        <svg class="h-6 w-6 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 font-semibold text-lime-400">Office Address</h3>
                                        <p class="text-emerald-200/70">
                                            123 Business St, Suite 100<br />
                                            City, State 12345
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 ring-2 ring-emerald-400/30 shadow-[0_0_15px_rgba(52,211,153,0.2)]">
                                        <svg class="h-6 w-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 font-semibold text-emerald-400">Phone</h3>
                                        <p class="text-emerald-200/70">+1 (555) 123-4567</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500/20 to-lime-500/20 ring-2 ring-cyan-400/30 shadow-[0_0_15px_rgba(6,182,212,0.2)]">
                                        <svg class="h-6 w-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 font-semibold text-cyan-400">Email</h3>
                                        <p class="text-emerald-200/70">hello@frankencms.com</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-lime-500/20 to-emerald-500/20 ring-2 ring-lime-400/30 shadow-[0_0_15px_rgba(163,230,53,0.2)]">
                                        <svg class="h-6 w-6 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 font-semibold text-lime-400">Business Hours</h3>
                                        <p class="text-emerald-200/70">
                                            Monday - Friday: 9:00 AM - 6:00 PM<br />
                                            Weekend: Closed
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Social Media Links --}}
                            <div class="mt-8">
                                <h3 class="mb-4 font-semibold text-emerald-400">Follow Us</h3>
                                <div class="flex gap-3">
                                    <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-lime-500/20 to-emerald-500/20 text-lime-400 ring-2 ring-lime-400/30 shadow-[0_0_10px_rgba(163,230,53,0.2)] transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:ring-cyan-400/50 hover:shadow-[0_0_20px_rgba(6,182,212,0.5)]">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                        </svg>
                                    </a>
                                    <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 text-emerald-400 ring-2 ring-emerald-400/30 shadow-[0_0_10px_rgba(52,211,153,0.2)] transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:ring-cyan-400/50 hover:shadow-[0_0_20px_rgba(6,182,212,0.5)]">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500/20 to-lime-500/20 text-cyan-400 ring-2 ring-cyan-400/30 shadow-[0_0_10px_rgba(6,182,212,0.2)] transition-all duration-300 hover:scale-110 hover:text-lime-400 hover:ring-lime-400/50 hover:shadow-[0_0_20px_rgba(163,230,53,0.5)]">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </x-slot>
</x-theme::layouts.main>
