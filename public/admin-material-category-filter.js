(() => {
    const initializeCategoryFilter = () => {
        const typeField = document.querySelector('[data-material-type-source]');
        const categoryField = document.querySelector('[data-material-category-target]');

        if (!typeField || !categoryField || categoryField.dataset.materialCategoryFilterInitialized === 'true') {
            return;
        }

        const categorySelect = categoryField.tomselect;
        if (!categorySelect) {
            return;
        }

        categoryField.dataset.materialCategoryFilterInitialized = 'true';

        const categories = Array.from(categoryField.options)
            .filter((option) => option.value !== '')
            .map((option) => ({
                value: option.value,
                text: option.text,
                typeId: option.dataset.materialTypeId,
            }));

        const filterCategories = () => {
            const typeId = String(typeField.tomselect?.getValue() ?? typeField.value ?? '');
            const selectedCategory = String(categorySelect.getValue() ?? '');
            const availableCategories = categories.filter((category) => category.typeId === typeId);
            const selectedCategoryIsAvailable = availableCategories.some(
                (category) => category.value === selectedCategory,
            );

            categorySelect.clear(true);
            categorySelect.clearOptions();
            categorySelect.addOptions(availableCategories);

            if (selectedCategoryIsAvailable) {
                categorySelect.setValue(selectedCategory, true);
            }

            categorySelect.refreshOptions(false);
        };

        typeField.addEventListener('change', filterCategories);
        filterCategories();
    };

    document.addEventListener('ea.autocomplete.connect', initializeCategoryFilter);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCategoryFilter);
    } else {
        initializeCategoryFilter();
    }
})();
