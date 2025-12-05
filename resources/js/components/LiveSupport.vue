<script setup lang="ts">
    import { onMounted, onBeforeUnmount } from 'vue';

    interface Props {
        apiKey?: string;
    }

    const props = withDefaults(defineProps<Props>(), {
        apiKey: '8eb6a4485f2fe0d54cd3df29b1b105f5cc211a03'
    });

    onMounted(() => {
        if (!window.smartsupp) {
            window._smartsupp = window._smartsupp || {};
            window._smartsupp.key = props.apiKey;

            const smartsupp = function(...args: any[]) {
                smartsupp._.push(args);
            };
            smartsupp._ = [];
            window.smartsupp = smartsupp;

            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.charset = 'utf-8';
            script.async = true;
            script.src = 'https://www.smartsuppchat.com/loader.js?';
            script.id = 'smartsupp-script';

            const firstScript = document.getElementsByTagName('script')[0];
            firstScript.parentNode?.insertBefore(script, firstScript);
        } else {
            if (window.smartsupp) {
                window.smartsupp('chat:show');
            }
        }
    });

    onBeforeUnmount(() => {
        if (window.smartsupp) {
            window.smartsupp('chat:close');
            window.smartsupp('chat:hide');
        }
    });
</script>

<template>
    <div></div>
</template>
