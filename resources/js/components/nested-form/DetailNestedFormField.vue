<template>
    <template v-if="field.useTabs">
        <Card class="mb-4 nested-form-container">
            <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 md:flex items-center">
                <ul class="flex flex-wrap -mb-px">
                    <template
                        v-for="(child, childIndex) in field.children"
                        :key="`heading-${resourceName}-${child.id || child.key}`"
                    >
                        <template v-if="isActiveTab(childIndex)">
                            <li class="mr-2">
                                <span
                                    class="cursor-default inline-block p-4 border-b-2 font-bold border-primary-500 text-primary-500"
                                    >{{ getHeadingText(child, childIndex) }}
                                </span>
                            </li>
                        </template>
                        <template v-else>
                            <li class="mr-2">
                                <a
                                    @click="setActive(childIndex)"
                                    class="cursor-pointer inline-block p-4 border-b-2 border-transparent hover:text-primary-500"
                                >
                                    {{ getHeadingText(child, childIndex) }}
                                </a>
                            </li>
                        </template>
                    </template>
                </ul>
            </div>
            <template v-for="(child, childIndex) in field.children" :key="`${resourceName}-${child.id || child.key}`">
                <div class="mb-4 nested-form-container" v-if="isActiveTab(childIndex)">
                    <div class="divide-gray-100 divide-y px-6">
                        <component
                            v-for="(childField, childFieldIndex) in child.fields"
                            :key="`${resourceName}-${child.id || child.key}-${childFieldIndex}`"
                            :index="childFieldIndex"
                            :is="resolveComponentName(child, childField)"
                            :resource="resource"
                            :resource-id="child.resourceId"
                            :resource-name="field.resourceName"
                            :field="childField"
                            @actionExecuted="$emit('actionExecuted')"
                        />
                    </div>
                </div>
            </template>
        </Card>
    </template>
    <template v-else>
        <Card
            v-for="(child, childIndex) in field.children"
            class="mb-4"
            :key="`${resourceName}-${child.id || child.key}`"
        >
            <Heading
                :level="3"
                class="bg-gray-200 px-6 py-2 rounded-t-lg shadow flex"
                :class="{ 'rounded-b-lg': !child.opened }"
            >
                {{ getHeadingText(child, childIndex) }}
                <div
                    class="ml-2 cursor-pointer flex items-center justify-center h-4 w-4"
                    @click="toggleVisibility(child)"
                >
                    <Icon class="cursor-pointer" :type="child.opened ? 'chevron-up' : 'chevron-down'" />
                </div>
            </Heading>
            <div class="divide-gray-100 divide-y px-6">
                <component
                    v-for="(childField, childFieldIndex) in child.fields"
                    :key="`${resourceName}-${child.id || child.key}-${childFieldIndex}`"
                    :index="childFieldIndex"
                    :is="resolveComponentName(child, childField)"
                    :resource="resource"
                    :resource-id="child.resourceId"
                    :resource-name="field.resourceName"
                    :field="childField"
                    @actionExecuted="$emit('actionExecuted')"
                    v-show="child.opened"
                />
            </div>
        </Card>
    </template>
</template>
<style scoped>
    .border-b-2 {
        border-bottom-width: 2px;
    }
    .underline-offset-4 {
        text-underline-offset: 4px;
    }
    .underline {
        text-decoration-line: underline;
    }
</style>
<script>
    import Tabbable from './mixins/tabbable';

    export default {
        props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
        mixins: [Tabbable],
        created() {
            this.assignDefaultTab();
        },
        methods: {
            getFieldsHeadingAttributes(child) {
                return this.field.heading.map(attribute => `${child.attribute}[${attribute}]`);
            },
            isHeadingField(child, field) {
                const isHeading = this.getFieldsHeadingAttributes(child).includes(field.attribute);
                return isHeading;
            },
            getHeadingText(child, index) {
                if (this.field.heading.length === 0) {
                    return `${this.field.singularLabel} ${this.field.keyName}: ${child[this.field.keyName]}`;
                }

                return this.getFieldsHeadingAttributes(child)
                    .map(attribute => {
                        return child.fields.find(field => field.attribute === attribute).value;
                    })
                    .join(this.field.separator);
            },

            resolveComponentName(child, field) {
                return this.isHeadingField(child, field)
                    ? 'detail-hidden-field'
                    : this.resolveDetailComponentName(field);
            },
            resolveDetailComponentName(field) {
                return field.prefixComponent ? 'detail-' + field.component : field.component;
            },
            toggleVisibility(child) {
                child.opened = !child.opened;
            },
            assignDefaultTab() {
                if (this.field.activeTab !== null) {
                    if (
                        typeof this.field.activeTab === 'number' &&
                        this.field.activeTab < this.field.children &&
                        this.field.activeTab >= 0
                    ) {
                        this.setActive(this.field.activeTab);
                    } else if (typeof this.field.activeTab === 'string') {
                        for (let x = 0; x < this.field.children.length; x++) {
                            const heading = this.getHeadingText(this.field.children[x], x);
                            if (heading === this.field.activeTab) {
                                this.setActive(x);
                                break;
                            }
                        }
                    }
                }
            }
        }
    };
</script>
