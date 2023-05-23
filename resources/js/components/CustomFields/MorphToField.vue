<script>
    import MorphToField from '@/fields/Form/MorphToField.vue';
    import storage from '@/storage/MorphToFieldStorage';

    export default {
        mixins: [MorphToField],
        methods: {
            /**
             * Get the resources that may be related to this resource.
             */
            getAvailableResources(search = '') {
                return storage
                    .fetchAvailableResources(this.resourceName, this.field.belongsToRelationship, this.queryParams)
                    .then(({ data: { resources, softDeletes, withTrashed } }) => {
                        if (this.initializingWithExistingResource || !this.isSearchable) {
                            this.withTrashed = withTrashed;
                        }

                        this.initializingWithExistingResource = false;
                        this.availableResources = resources;
                        this.softDeletes = softDeletes;
                    });
            }
        }
    };
</script>
