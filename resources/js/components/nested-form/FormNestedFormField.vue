<template>
    <template v-if="currentField.useTabs">
        <div class="nested-form-container">
            <Card class="mb-4">
                <div
                    class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 md:flex items-center"
                >
                    <ul class="flex flex-wrap -mb-px">
                        <template
                            v-for="(child, childIndex) in children"
                            :key="`heading-${resourceName}-${child.id || child.key}-${updateKey}`"
                        >
                            <template v-if="isActiveTab(childIndex)">
                                <li class="mr-2">
                                    <span
                                        :class="{
                                            'line-through border-gray-500 text-gray-500':
                                                isRestorable(childIndex) && !hasErrors(child),
                                            'border-red-500 text-red-500': hasErrors(child),
                                            'border-primary-500 text-primary-500':
                                                !isRestorable(childIndex) && !hasErrors(child)
                                        }"
                                        class="cursor-default inline-block p-4 border-b-2 font-bold"
                                        >{{ getHeadingText(child, childIndex) }}
                                    </span>
                                </li>
                            </template>
                            <template v-else>
                                <li class="mr-2">
                                    <a
                                        @click="setActive(childIndex)"
                                        :class="{
                                            'line-through hover:text-gray-500':
                                                isRestorable(childIndex) && !hasErrors(child),
                                            'border-red-500 border-b-2 hover:text-red-500': hasErrors(child),
                                            'hover:text-primary-500': !isRestorable(childIndex) && !hasErrors(child)
                                        }"
                                        class="cursor-pointer inline-block p-4 border-b-2 border-transparent"
                                    >
                                        {{ getHeadingText(child, childIndex) }}
                                    </a>
                                </li>
                            </template>
                        </template>
                    </ul>
                    <div class="ml-auto flex items-center mr-2 mb-2 mt-2" v-if="canAdd">
                        <a
                            class="whitespace-nowrap shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900"
                            @click.prevent="addChild"
                        >
                            {{ currentField.addText }}
                        </a>
                    </div>
                </div>
                <template
                    v-for="(child, childIndex) in children"
                    :key="`${resourceName}-${child.id || child.key}-${updateKey}`"
                    v-if="children.length"
                >
                    <div class="mb-4" v-show="isActiveTab(childIndex)">
                        <div
                            class="divide-y"
                            :class="[isRestorable(childIndex) ? 'bg-yellow-100 divide-gray-800' : 'divide-gray-100']"
                        >
                            <component
                                :errors="errors"
                                :field="childField"
                                :index="childIndex"
                                :sync-endpoint="resolveSyncEndpoint(childIndex, child.resourceId)"
                                :is="resolveComponentName(child, childField)"
                                :parent-index="index"
                                :resource-id="child.resourceId"
                                :resource-name="currentField.resourceName"
                                :show-help-text="childField.helpText != null"
                                :via-resource="currentField.viaResource"
                                :via-resource-id="currentField.viaResourceId"
                                @file-deleted="$emit('file-deleted')"
                                :key="`${resourceName}-${child.id || child.key}-${childFieldIndex}-${updateKey}`"
                                v-for="(childField, childFieldIndex) in child.fields"
                            />
                            <div class="flex justify-end space-x-2 p-2">
                                <a
                                    class="whitespace-nowrap shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900"
                                    @click.prevent="duplicateChild(childIndex)"
                                    v-if="canAdd && currentField.canDuplicate"
                                >
                                    {{ currentField.duplicateText }}
                                </a>
                                <a
                                    class="whitespace-nowrap shadow relative bg-red-500 hover:bg-red-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-red-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-red-500 hover:bg-red-400 text-white dark:text-gray-900"
                                    @click.prevent="removeChild(childIndex)"
                                    v-if="canRemove && !isRestorable(childIndex)"
                                >
                                    {{ currentField.removeText }}
                                </a>
                                <a
                                    class="whitespace-nowrap shadow relative bg-green-500 hover:bg-green-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-green-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-green-500 hover:bg-green-400 text-white dark:text-gray-900"
                                    @click.prevent="restoreChild(childIndex)"
                                    v-if="isRestorable(childIndex)"
                                >
                                    {{ currentField.restoreText }}
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="flex flex-col justify-center items-center px-6 py-8" v-else>
                    <svg
                        class="inline-block text-gray-300 dark:text-gray-500"
                        xmlns="http://www.w3.org/2000/svg"
                        width="65"
                        height="51"
                        viewBox="0 0 65 51"
                    >
                        <path
                            class="fill-current"
                            d="M56 40h2c.552285 0 1 .447715 1 1s-.447715 1-1 1h-2v2c0 .552285-.447715 1-1 1s-1-.447715-1-1v-2h-2c-.552285 0-1-.447715-1-1s.447715-1 1-1h2v-2c0-.552285.447715-1 1-1s1 .447715 1 1v2zm-5.364125-8H38v8h7.049375c.350333-3.528515 2.534789-6.517471 5.5865-8zm-5.5865 10H6c-3.313708 0-6-2.686292-6-6V6c0-3.313708 2.686292-6 6-6h44c3.313708 0 6 2.686292 6 6v25.049375C61.053323 31.5511 65 35.814652 65 41c0 5.522847-4.477153 10-10 10-5.185348 0-9.4489-3.946677-9.950625-9zM20 30h16v-8H20v8zm0 2v8h16v-8H20zm34-2v-8H38v8h16zM2 30h16v-8H2v8zm0 2v4c0 2.209139 1.790861 4 4 4h12v-8H2zm18-12h16v-8H20v8zm34 0v-8H38v8h16zM2 20h16v-8H2v8zm52-10V6c0-2.209139-1.790861-4-4-4H6C3.790861 2 2 3.790861 2 6v4h52zm1 39c4.418278 0 8-3.581722 8-8s-3.581722-8-8-8-8 3.581722-8 8 3.581722 8 8 8z"
                        />
                    </svg>

                    <h3 class="text-base font-normal mt-3">
                        {{ __(`No ${currentField.pluralLabel} detected.`) }}
                    </h3>
                </div>
            </Card>
            <HelpText class="mt-2 help-text-error" v-if="hasError">
                {{ firstError }}
            </HelpText>
        </div>
    </template>
    <template v-else>
        <div class="nested-form-container">
            <Card
                v-for="(child, childIndex) in children"
                class="mb-4"
                :class="{ 'border-2 border-red-500': hasErrors(child) }"
                :key="`${resourceName}-${child.id || child.key}-${updateKey}`"
            >
                <Heading
                    :level="3"
                    class="bg-gray-200 px-6 py-2 rounded-t-lg shadow flex"
                    :class="{ 'rounded-b-lg': !child.opened, 'bg-yellow-300': isRestorable(childIndex) }"
                >
                    <div class="flex">
                        <span :class="{ 'line-through': isRestorable(childIndex) }">{{
                            getHeadingText(child, childIndex)
                        }}</span>
                        <div
                            class="ml-2 cursor-pointer flex items-center justify-center h-4 w-4"
                            @click="toggleVisibility(child)"
                        >
                            <Icon class="cursor-pointer" :type="child.opened ? 'chevron-up' : 'chevron-down'" />
                        </div>
                    </div>

                    <div class="ml-auto flex items-center space-x-2">
                        <div
                            class="cursor-pointer hover:border-primary-500 hover:text-primary-500 flex items-center justify-center h-4 w-4"
                            @click="duplicateChild(childIndex)"
                            v-if="canAdd && currentField.canDuplicate"
                            :title="currentField.duplicateText"
                        >
                            <Icon type="document-duplicate" />
                        </div>
                        <div
                            class="cursor-pointer hover:border-red-500 hover:text-red-500 flex items-center justify-center h-4 w-4"
                            @click="removeChild(childIndex)"
                            v-if="canRemove && !isRestorable(childIndex)"
                            :title="currentField.removeText"
                        >
                            <Icon type="trash" />
                        </div>
                        <div
                            class="cursor-pointer hover:border-green-500 hover:text-green-500 flex items-center justify-center h-4 w-4"
                            @click="restoreChild(childIndex)"
                            v-if="isRestorable(childIndex)"
                            :title="currentField.restoreText"
                        >
                            <Icon type="reply" />
                        </div>
                    </div>
                </Heading>
                <div
                    class="divide-y"
                    :class="[isRestorable(childIndex) ? 'bg-yellow-100 divide-gray-800' : 'divide-gray-100']"
                >
                    <component
                        :errors="errors"
                        :field="childField"
                        :index="childIndex"
                        :sync-endpoint="resolveSyncEndpoint(childIndex, child.resourceId)"
                        :is="resolveComponentName(child, childField)"
                        :parent-index="index"
                        :resource-id="child.resourceId"
                        :resource-name="currentField.resourceName"
                        :show-help-text="childField.helpText != null"
                        :via-resource="currentField.viaResource"
                        :via-resource-id="currentField.viaResourceId"
                        @file-deleted="$emit('file-deleted')"
                        :key="`${resourceName}-${child.id || child.key}-${updateKey}-${childFieldIndex}`"
                        v-for="(childField, childFieldIndex) in child.fields"
                        v-show="child.opened"
                    />
                </div>
            </Card>
            <HelpText class="mt-2 help-text-error" v-if="hasError">
                {{ firstError }}
            </HelpText>
            <div class="flex py-4" v-if="canAdd">
                <div class="ml-auto flex items-center">
                    <a
                        class="shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900"
                        @click.prevent="addChild"
                    >
                        {{ currentField.addText }}
                    </a>
                </div>
            </div>
        </div>
    </template>
    <template v-if="hasHeadingChildren">
        <Modal :show="showModal" @close-via-escape="closeModal" tabindex="-1" role="dialog">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden space-y-6">
                <div class="space-y-6">
                    <ModalHeader>{{ currentField.addText }}</ModalHeader>
                    <template
                        v-for="(childField, childFieldIndex) in childToAdd.fields"
                        :key="`${resourceName}-${childToAdd.id || childToAdd.key}-${childFieldIndex}`"
                    >
                        <component
                            :errors="errors"
                            :field="childField"
                            :index="children.length"
                            :is="resolveFormComponentName(childField)"
                            :sync-endpoint="resolveSyncEndpoint(children.length, childToAdd.resourceId)"
                            :parent-index="index"
                            :resource-id="childToAdd.resourceId"
                            :resource-name="currentField.resourceName"
                            :show-help-text="childField.helpText != null"
                            :via-resource="currentField.viaResource"
                            :via-resource-id="currentField.viaResourceId"
                            @file-deleted="$emit('file-deleted')"
                            mode="action-modal"
                            v-if="isHeadingField(childToAdd, childField)"
                        />
                    </template>
                    <p class="px-8 text-red-500" v-if="modalError">
                        {{ modalError }}
                    </p>
                    <p class="px-8 text-green-500" v-if="isCreated">
                        {{ __(`${currentField.singularLabel} successfully created.`) }}
                    </p>
                </div>
                <ModalFooter>
                    <div class="flex items-center ml-auto">
                        <CancelButton
                            component="button"
                            type="button"
                            class="ml-auto"
                            :disabled="isWorking"
                            @click="closeModal"
                        >
                            {{ __('Close') }}
                        </CancelButton>
                        <LoadingButton type="button" @click="addToChildren" :disabled="isWorking" :loading="isWorking">
                            {{ __('Add') }}
                        </LoadingButton>
                    </div>
                </ModalFooter>
            </div>
        </Modal>
    </template>
</template>
<style scoped>
    .hover\:border-green-500:hover {
        --tw-border-opacity: 1;
        border-color: rgb(34 197 94 / var(--tw-border-opacity));
    }
    .hover\:border-red-500:hover {
        --tw-border-opacity: 1;
        border-color: rgb(239 68 68 / var(--tw-border-opacity));
    }
    .hover\:text-green-500:hover {
        --tw-text-opacity: 1;
        color: rgb(34 197 94 / var(--tw-text-opacity));
    }
    .hover\:text-red-500:hover {
        --tw-text-opacity: 1;
        color: rgb(239 68 68 / var(--tw-text-opacity));
    }
    .line-through {
        text-decoration-line: line-through;
    }
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
    import { DependentFormField, HandlesValidationErrors } from 'laravel-nova';
    import Tabbable from './mixins/tabbable';

    export default {
        mixins: [DependentFormField, HandlesValidationErrors, Tabbable],
        props: ['resourceName', 'resourceId', 'field', 'index', 'parentIndex'],

        data() {
            return {
                restorables: [],
                childToAdd: null,
                modalError: '',
                children: [],
                updateKey: 0,
                isWorking: false,
                isCreated: false,
                createdTimeout: null
            };
        },
        computed: {
            canAdd() {
                return (
                    (this.currentField.max === 0 || this.children.length < this.currentField.max) &&
                    !this.currentField.locked
                );
            },
            canRemove() {
                return this.children.length > this.currentField.min && !this.currentField.locked;
            },
            hasHeadingChildren() {
                return this.currentField.heading.length > 0;
            },
            showModal() {
                return this.childToAdd !== null;
            }
        },
        created() {
            this.assignDefaultChildren();
            this.assignDefaultTab();
        },
        methods: {
            getFieldsHeadingAttributes(child) {
                return this.currentField.heading.map(attribute => `${child.attribute}[${attribute}]`);
            },
            isHeadingField(child, field) {
                const isHeading = this.getFieldsHeadingAttributes(child).includes(field.attribute);
                return isHeading;
            },
            getHeadingText(child, index) {
                if (this.currentField.heading.length === 0) {
                    return child[this.currentField.keyName]
                        ? `${this.currentField.singularLabel} ${this.currentField.keyName}: ${
                              child[this.currentField.keyName]
                          }`
                        : `${index + 1}${this.currentField.separator}${this.currentField.singularLabel}`;
                }

                return this.getFieldsHeadingAttributes(child)
                    .map(attribute => {
                        return child.fields.find(field => field.attribute === attribute).value;
                    })
                    .join(this.currentField.separator);
            },
            getAllChildrenHeadingFields() {
                const others = [];

                for (let x = 0; x < this.children.length; x++) {
                    const children = this.children[x];
                    const fieldsHeadingAttributes = this.getFieldsHeadingAttributes(children);
                    others.push(
                        children.fields
                            .filter(field => fieldsHeadingAttributes.includes(field.attribute))
                            .reduce((carry, field) => {
                                carry[field.attribute.replace(children.attribute, this.childToAdd.attribute)] =
                                    field.conditionalField ? field.conditionalField.value : field.value;
                                return carry;
                            }, {})
                    );
                }

                return others;
            },
            assignDefaultChildren() {
                if (!this.resourceId) {
                    for (let i = 0; i < this.currentField.min; i++) {
                        this.children.push(this.generateNewChild());
                    }
                    for (const prefill of this.currentField.prefill) {
                        const child = this.generateNewChild();
                        this.children.push(this.addPrefill(child, prefill));
                    }
                } else {
                    this.children = JSON.parse(JSON.stringify(this.currentField.children));
                }
            },
            addPrefill(child, prefill) {
                for (let x = 0; x < child.fields.length; x++) {
                    const attribute = child.fields[x].attribute.replace(child.attribute, '');
                    if (attribute in prefill) {
                        if (child.fields[x].conditionalField) {
                            child.fields[x].conditionalField.value = prefill[attribute];
                        } else {
                            child.fields[x].value = prefill[attribute];
                        }
                    }
                }
                return child;
            },

            assignDefaultTab() {
                if (this.currentField.activeTab !== null) {
                    if (
                        typeof this.currentField.activeTab === 'number' &&
                        this.currentField.activeTab < this.children &&
                        this.currentField.activeTab >= 0
                    ) {
                        this.setActive(this.currentField.activeTab);
                    } else if (typeof this.currentField.activeTab === 'string') {
                        for (let x = 0; x < this.children.length; x++) {
                            const heading = this.getHeadingText(this.children[x], x);
                            if (heading === this.currentField.activeTab) {
                                this.setActive(x);
                                break;
                            }
                        }
                    }
                }
            },
            onSyncedField() {
                this.updateKey++;
                if (this.children.length === 0) {
                    this.assignDefaultChildren();
                } else {
                    if (
                        !this.resourceId &&
                        this.currentField.forcePrefill &&
                        this.currentField.prefill.length > 0 &&
                        this.currentField.prefill.length < this.children.length
                    ) {
                        this.children = this.children.slice(0, this.currentField.prefill.length);
                    }

                    for (let x = 0; x < this.children.length; x++) {
                        this.children.splice(
                            x,
                            1,
                            this.updateChildrenFromSchema(this.currentField, this.children[x], x)
                        );
                    }

                    if (!this.resourceId && this.currentField.forcePrefill) {
                        for (let x = this.children.length; x < this.currentField.prefill.length; x++) {
                            const child = this.generateNewChild();
                            this.children.push(this.addPrefill(child, this.currentField.prefill[x]));
                        }
                    }
                }
            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                this.value = value;
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {
                try {
                    this.children.forEach((child, childIndex) => {
                        if (!this.restorables.includes(childIndex)) {
                            if (child[this.currentField.keyName]) {
                                formData.append(
                                    `${child.attribute}[${this.currentField.keyName}]`,
                                    child[this.currentField.keyName]
                                );
                            }
                            child.fields.forEach(field => {
                                field.fill(formData);
                            });
                        }
                    });

                    const regex = /(.*?(?:\[.*?\])+)(\[.*?)\]((?!\[).+)$/;

                    for (const [key, value] of formData.entries()) {
                        if (key.match(regex)) {
                            formData.append(key.replace(regex, '$1$2$3]'), value);
                            formData.delete(key);
                        }
                    }
                } catch (error) {
                    console.log(error);
                    throw error;
                }
            },

            hasErrors(child) {
                return (
                    this.errors &&
                    Object.keys(this.errors.all()).filter(key => key.startsWith(child.attribute)).length > 0
                );
            },

            resolveComponentName(child, field) {
                return this.isHeadingField(child, field) ? 'form-hidden-field' : this.resolveFormComponentName(field);
            },
            resolveFormComponentName(field) {
                return field.prefixComponent ? 'form-' + field.component : field.component;
            },
            resolveSyncEndpoint(index, resourceId) {
                const nestedPrefix = this.currentField.schema.attribute.replace(this.currentField.indexKey, index);
                if (resourceId !== '' && !_.isNil(resourceId)) {
                    return `/nova-vendor/nested-form/${nestedPrefix}/${this.currentField.resourceName}/${resourceId}/update-fields`;
                }

                return `/nova-vendor/nested-form/${nestedPrefix}/${this.currentField.resourceName}/creation-fields`;
            },

            toggleVisibility(child) {
                child.opened = !child.opened;
            },

            isRestorable(childIndex) {
                return this.restorables.includes(childIndex);
            },

            removeChild(childIndex) {
                if (this.currentField.canRestore) {
                    if (!this.restorables.includes(childIndex)) {
                        this.restorables.push(childIndex);
                    }
                } else {
                    this.children.splice(childIndex, 1);
                }
            },
            restoreChild(childIndex) {
                const index = this.restorables.indexOf(childIndex);
                if (index > -1) {
                    this.restorables.splice(index, 1);
                }
            },

            duplicateChild(childIndex) {
                const newChild = this.generateNewChild();
                const headingFieldsToExclude = this.getFieldsHeadingAttributes(newChild);

                const originalField = JSON.parse(JSON.stringify(this.children[childIndex]));
                const originalAttributeKey = originalField.attribute;
                const newAttributeKey = newChild.attribute;

                const mappedValues = originalField.fields.reduce((carry, field) => {
                    carry[field.attribute.replace(originalAttributeKey, newAttributeKey)] = field.conditionalField
                        ? field.conditionalField.value
                        : field.value;

                    return carry;
                }, {});

                for (let x = 0; x < newChild.fields.length; x++) {
                    if (headingFieldsToExclude.includes(newChild.fields[x].attribute)) {
                        continue;
                    }
                    if (newChild.fields[x].conditionalField) {
                        newChild.fields[x].conditionalField.value = mappedValues[newChild.fields[x].attribute];
                    } else {
                        newChild.fields[x].value = mappedValues[newChild.fields[x].attribute];
                    }
                }

                this.addMandatoryFields(newChild);
            },

            addChild() {
                this.addMandatoryFields(this.generateNewChild());
            },

            addMandatoryFields(field) {
                if (this.hasHeadingChildren) {
                    this.childToAdd = field;
                } else {
                    this.children.push(field);
                    this.setActive(this.children.length - 1);
                }
            },

            addToChildren() {
                this.isCreated = false;
                this.isWorking = true;
                this.modalError = '';
                const newHeadingFieldsAttributes = this.getFieldsHeadingAttributes(this.childToAdd);
                const formdata = new FormData();

                this.childToAdd.fields
                    .filter(field => newHeadingFieldsAttributes.includes(field.attribute))
                    .forEach(field => {
                        field.fill(formdata);
                    });

                const newHeadingsFields = Object.fromEntries(formdata.entries());

                this.modalError =
                    Object.values(newHeadingsFields).filter(value => value === null || value === '').length > 0
                        ? this.__('All fields are required')
                        : '';

                if (this.modalError) {
                    this.isWorking = false;
                    return;
                }

                const headingsFieldsToCompare = this.currentField.uniqueHeading
                    ? this.getAllChildrenHeadingFields()
                    : [];

                let notUnique = false;

                for (let x = 0; x < headingsFieldsToCompare.length; x++) {
                    for (const key in headingsFieldsToCompare[x]) {
                        const compareValue = headingsFieldsToCompare[x][key];
                        const newValue = newHeadingsFields[key];
                        if (compareValue !== newValue) {
                            notUnique = false;
                            break;
                        }

                        notUnique = true;
                    }
                    if (notUnique) {
                        break;
                    }
                }

                this.modalError = notUnique ? this.__('Field Values Already Taken') : '';

                if (this.modalError) {
                    this.isWorking = false;
                    return;
                }

                this.childToAdd.fields.forEach(field => {
                    if (field.attribute in newHeadingsFields) {
                        if (field.conditionalField) {
                            field.conditionalField.value = newHeadingsFields[field.attribute];
                        } else {
                            field.value = newHeadingsFields[field.attribute];
                        }
                    }
                });

                this.children.push(JSON.parse(JSON.stringify(this.childToAdd)));

                this.setActive(this.children.length - 1);
                this.modalError = '';
                this.addChild();
                this.isWorking = false;
                this.isCreated = true;
                this.createdTimeout = setTimeout(() => {
                    this.isCreated = false;
                }, 1500);
            },

            closeModal() {
                clearTimeout(this.createdTimeout);
                this.isCreated = false;
                this.modalError = '';
                this.childToAdd = null;
            },

            getNextKey(children) {
                let maxKey = 0;
                if (children && children.length) {
                    maxKey =
                        children[children.length - 1].key ||
                        Math.max.apply(
                            Math,
                            children.map(({ id }) => id)
                        );
                }
                return maxKey + 1;
            },

            generateNewChild() {
                if (!this.currentField.schema) {
                    this.currentField.schema = {};
                }

                return this.replaceIndexesInSchema(this.currentField, this.getNextKey(this.children));
            },

            replaceIndexOnField(field, indexKey, indexLength) {
                if (field.attribute) {
                    field.attribute = field.attribute.replace(indexKey, indexLength);
                }

                if (field.dependsOn) {
                    field.dependsOn = Object.keys(field.dependsOn).reduce((carry, key) => {
                        carry[key.replace(indexKey, indexLength)] = field.dependsOn[key];
                        return carry;
                    }, {});
                }

                if (field.validationKey) {
                    field.validationKey = field.validationKey.replace(indexKey, indexLength);
                }

                if (field.conditionalField) {
                    field.conditionalField = this.replaceIndexOnField(field.conditionalField, indexKey, indexLength);
                }

                return field;
            },

            replaceIndexesInSchema(field, key) {
                const schema = JSON.parse(JSON.stringify(field.schema));

                schema.key = key;

                schema.attribute = schema.attribute.replace(this.currentField.indexKey, this.children.length);

                schema.fields &&
                    schema.fields.forEach(field => {
                        this.replaceIndexOnField(field, this.currentField.indexKey, this.children.length);

                        // //to map dependency container field
                        // const parent = this;
                        // if (field.fields) {
                        //     field.fields.forEach(field => {
                        //         this.replaceIndexOnField(field, parent.field.indexKey, parent.field.children.length);
                        //     });
                        // }
                    });

                return schema;
            },

            updateChildrenField(field, child, indexKey, indexLength) {
                if (field.attribute) {
                    field.attribute = field.attribute.replace(indexKey, indexLength);
                }

                const prefill = this.currentField.prefill[indexLength];

                const currentField = child.fields.find(originalField => originalField.attribute === field.attribute);
                let value = undefined;

                if (currentField) {
                    const formdata = new FormData();
                    currentField.fill(formdata);
                    const values = Object.fromEntries(formdata.entries());
                    value = field.attribute in values ? values[field.attribute] : value;
                }

                if (!this.resourceId && prefill) {
                    const attribute = field.attribute.replace(child.attribute, '');
                    if (attribute in prefill) {
                        value = prefill[attribute];
                    }
                }

                if (field.dependsOn) {
                    field.dependsOn = Object.keys(field.dependsOn).reduce((carry, key) => {
                        carry[key.replace(indexKey, indexLength)] = field.dependsOn[key];
                        return carry;
                    }, {});
                }

                if (field.validationKey) {
                    field.validationKey = field.validationKey.replace(indexKey, indexLength);
                }

                if (field.conditionalField) {
                    field.conditionalField = this.updateChildrenField(
                        field.conditionalField,
                        child,
                        indexKey,
                        indexLength
                    );
                } else {
                    if (field.component === 'boolean-field') {
                        value = value == '0' ? false : true;
                    }
                    field.value = value !== undefined ? (value === '' ? null : value) : field.value;
                }

                return field;
            },

            updateChildrenFromSchema(field, child, childIndex) {
                const schema = JSON.parse(JSON.stringify(field.schema));

                schema.key = child.key;
                schema.resourceId = child.resourceId;
                schema[this.currentField.keyName] = child[this.currentField.keyName];

                schema.attribute = schema.attribute.replace(this.currentField.indexKey, childIndex);

                schema.fields &&
                    schema.fields.forEach(field => {
                        this.updateChildrenField(field, child, this.currentField.indexKey, childIndex);

                        // //to map dependency container field
                        // const parent = this;
                        // if (field.fields) {
                        //     field.fields.forEach(field => {
                        //         this.updateChildrenField(field, parent.field.indexKey, parent.field.children.length);
                        //     });
                        // }
                    });

                return schema;
            }
        }
    };
</script>
