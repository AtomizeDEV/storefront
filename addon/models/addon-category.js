import CategoryModel from '@atomizedev/console/models/category';
import { hasMany } from '@ember-data/model';

export default class AddonCategoryModel extends CategoryModel {
    @hasMany('product-addon') addons;
}
