export default {
    data: () => ({
        activeTab: 0
    }),

    methods: {
        setActive(childIndex) {
            this.activeTab = childIndex;
        },
        isActiveTab(childIndex) {
            return this.activeTab === childIndex;
        }
    }
};
