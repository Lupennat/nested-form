<template>
    <div>
        <slot>
            <div class="flex items-center">
                <Heading :level="1" :class="panel.helpText ? 'mb-2' : 'mb-3'">{{ panel.name }}</Heading>
            </div>

            <p
                v-if="panel.helpText"
                class="text-gray-500 text-sm font-semibold italic"
                :class="panel.helpText ? 'mt-2' : 'mt-3'"
                v-html="panel.helpText"
            />
        </slot>
        <div>
            <component
                :key="index"
                v-for="(field, index) in panel.fields"
                :index="index"
                :is="resolveComponentName(field)"
                :resource-name="resourceName"
                :resource-id="resourceId"
                :resource="resource"
                :field="field"
                @actionExecuted="actionExecuted"
            />
        </div>
    </div>
</template>

<script>
    import { BehavesAsPanel } from '@/mixins';

    export default {
        mixins: [BehavesAsPanel],

        methods: {
            /**
             * Resolve the component name.
             */
            resolveComponentName(field) {
                return field.prefixComponent ? 'detail-' + field.component : field.component;
            }
        }
    };
</script>
