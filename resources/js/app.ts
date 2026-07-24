import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue', { eager: true })
        const page = pages[`./pages/${name}.vue`]

        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }

        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
