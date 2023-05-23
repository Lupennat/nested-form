import DetailNestedPanel from './components/nested-panel/DetailNestedPanel';
import FormNestedPanel from './components/nested-panel/FormNestedPanel';
import DetailNestedFormField from './components/nested-form/DetailNestedFormField';
import FormNestedFormField from './components/nested-form/FormNestedFormField';
import NestedFormBelongsToField from './components/CustomFields/BelongsToField';
import NestedFormFileField from './components/CustomFields/FileField';
import NestedFormMorphToField from './components/CustomFields/MorphToField';

Nova.booting(app => {
    app.component('detail-nested-panel', DetailNestedPanel);
    app.component('detail-nested-form', DetailNestedFormField);

    app.component('form-nested-panel', FormNestedPanel);
    app.component('form-nested-form', FormNestedFormField);

    app.component('form-nested-form-belongs-to-field', NestedFormBelongsToField);
    app.component('form-nested-form-morph-to-field', NestedFormMorphToField);
    app.component('form-nested-form-file-field', NestedFormFileField);
});
