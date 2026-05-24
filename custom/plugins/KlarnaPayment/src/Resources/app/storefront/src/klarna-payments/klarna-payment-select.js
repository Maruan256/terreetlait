import Plugin from 'src/plugin-system/plugin.class';

export default class KlarnaPaymentSelect extends Plugin {
	init() {
		if(!this.el) {
			return;
		}
		
		this.el.addEventListener("change", this.onChange.bind(this));
	}
	
	onChange() {
		document.getElementById("klarnaSelectedCategory").value = this.el.dataset.klarnaSelect;
	}
}