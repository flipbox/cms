<template>
	<div>
		<form @submit.prevent="checkout()" class="payment">
			<div class="blocks">
				<div class="block">
					<div v-if="staticCartTotal > 0">
						<h2>{{ "Payment Method"|t('app') }}</h2>

						<template v-if="craftId">
							<p v-if="craftId && craftId.card"><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card <span>{{ craftId.card.brand }} •••• •••• •••• {{ craftId.card.last4 }} — {{ craftId.card.exp_month }}/{{ craftId.card.exp_year }}</span></label></p>
							<p><label><input type="radio" value="newCard" v-model="paymentMode" /> Use a new credit card</label></p>

							<template v-if="paymentMode === 'newCard'">
								<credit-card v-if="!cardToken" ref="newCard"></credit-card>
								<p v-else>{{ cardToken.card.brand }} •••• •••• •••• {{ cardToken.card.last4 }} ({{ cardToken.card.exp_month }}/{{ cardToken.card.exp_year }}) <a class="delete icon" @click="cardToken = null"></a></p>
								<checkbox-field id="replaceCard" v-model="replaceCard" label="Save as my new credit card" />
							</template>
						</template>

						<template v-else>
							<credit-card ref="guestCard"></credit-card>
						</template>
					</div>

					<h2>{{ "Coupon Code"|t('app') }}</h2>
					<text-field placeholder="XXXXXXX" id="coupon-code" v-model="couponCode" size="12" @input="couponCodeChange" :errors="couponCodeError" />
					<div v-if="couponCodeLoading" class="spinner"></div>
				</div>

				<div class="block">
					<h2>{{ "Billing"|t('app') }}</h2>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="First Name" id="first-name" v-model="billingInfo.firstName" :errors="errors['billingAddress.firstName']" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Last Name" id="last-name" v-model="billingInfo.lastName" :errors="errors['billingAddress.lastName']" />
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Business Name" id="business-name" v-model="billingInfo.businessName" :errors="errors['billingAddress.businessName']" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Business Tax ID" id="business-tax-id" v-model="billingInfo.businessTaxId" :errors="errors['billingAddress.businessTaxId']" />
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Address Line 1" id="address-1" v-model="billingInfo.address1" :errors="errors['billingAddress.address1']" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Address Line 2" id="address-2" v-model="billingInfo.address2" :errors="errors['billingAddress.address2']" />
								</div>
								<div class="multitextrow">
									<input type="text" class="text" :class="{ error: errors['billingAddress.city'] }" placeholder="City" id="city" v-model="billingInfo.city" />
									<input type="text" class="text" :class="{ error: errors['billingAddress.zipCode'] }" placeholder="Zip Code" id="zip-code" v-model="billingInfo.zipCode" />
								</div>
								<div class="multiselectrow">
									<select-input v-model="billingInfo.country" :options="countryOptions" @input="onCountryChange" :errors="errors['billingAddress.country']" />
									<select-input v-model="billingInfo.state" :options="stateOptions" :errors="errors['billingAddress.state']" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<hr>

			<div class="centeralign">
				<p v-if="error" class="error">{{ error }}</p>

				<input type="submit" class="btn submit" :value="$options.filters.t('Pay', 'app')+ ' ' + $options.filters.currency(staticCartTotal)" />
				<div v-if="loading" class="spinner"></div>

				<p>
					<img :src="poweredByStripe" height="18" />
				</p>
			</div>
		</form>
	</div>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'

    export default {
        components: {
            CheckboxField: require('../fields/CheckboxField'),
            TextareaField: require('../fields/TextareaField'),
            TextField: require('../fields/TextField'),
            TextInput: require('../inputs/TextInput'),
            CreditCard: require('../CreditCard'),
            SelectInput: require('../inputs/SelectInput'),
        },

        data() {
            return {
                error: false,
                loading: false,
                paymentMode: 'newCard',
                cardToken: null,
                guestCardToken: null,
                replaceCard: false,
                couponCode: '',
                couponCodeLoading: false,
                couponCodeSuccess: false,
                couponCodeError: false,
                couponCodeTimeout: false,

                billingInfo: {
                    firstName: '',
                    lastName: '',
                    businessName: '',
                    businessTaxId: '',
                    address1: '',
                    address2: '',
                    country: '',
                    state: '',
                    city: '',
                    zipCode: '',
                },

                billingInfoErrors: {
                    businessTaxId: false,
                },

                errors: {},

                stateOptions: [],

                staticCartTotal: 0,
            }
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                poweredByStripe: state => state.craft.poweredByStripe,
                craftId: state => state.craft.craftId,
                countries: state => state.craft.countries,
                states: state => state.craft.states,
            }),

            countryOptions() {
                let options = []

                for (let iso in this.countries) {
                    if (this.countries.hasOwnProperty(iso)) {
                        options.push({
                            label: this.countries[iso].name,
                            value: iso,
                        })
                    }
                }

                return options
            },

            billingCountryName() {
                const iso = this.billingInfo.country

                if (!iso) {
                    return
                }

                if (!this.countries[iso]) {
                    return
                }

                return this.countries[iso].name
            }
        },

        methods: {

            savePaymentMethod(cb, cbError) {
                if (this.cart.totalPrice > 0) {
                    if (this.craftId) {
                        if (this.paymentMode === 'newCard') {
                            // Save new card
                            if (!this.cardToken) {
                                this.$refs.newCard.save(response => {
                                    this.cardToken = response
                                    cb()
                                }, () => {
                                    cbError()
                                })
                            } else {
                                cb()
                            }
                        } else {
                            cb()
                        }
                    } else {
                        // Save guest card
                        this.$refs.guestCard.save(response => {
                            this.guestCardToken = response
                            cb()
                        }, () => {
                            cbError()
                        })
                    }
                } else {
                    cb()
                }
            },

            saveBillingInfo(cb, cbError) {
                let cartData = {
                    billingAddress: {
                        firstName: this.billingInfo.firstName,
                        lastName: this.billingInfo.lastName,
                        businessName: this.billingInfo.businessName,
                        businessTaxId: this.billingInfo.businessTaxId,
                        address1: this.billingInfo.address1,
                        address2: this.billingInfo.address2,
                        country: this.billingInfo.country,
                        state: this.billingInfo.state,
                        city: this.billingInfo.city,
                        zipCode: this.billingInfo.zipCode,
                    },
                }

                this.$store.dispatch('saveCart', cartData)
                    .then(response => {
                        cb(response)
                    })
                    .catch(response => {
                        cbError(response)
                    })
            },

            checkout() {
                this.errors = {}
                this.loading = true
                this.savePaymentMethod(() => {
                    this.saveBillingInfo(() => {
                        // Ready to pay
                        let cardToken = null

                        if (this.cart.totalPrice > 0) {
                            if (this.craftId) {
                                switch (this.paymentMode) {
                                    case 'newCard':
                                        cardToken = this.cardToken.id
                                        break
                                    default:
                                        cardToken = this.craftId.cardToken
                                }
                            } else {
                                cardToken = this.guestCardToken.id
                            }
                        }

                        let checkoutData = {
                            orderNumber: this.cart.number,
                            token: cardToken,
                            expectedPrice: this.cart.totalPrice,
                            makePrimary: this.replaceCard,
                        }

                        this.$store.dispatch('checkout', checkoutData)
                            .then(response => {
                                this.$store.dispatch('savePluginLicenseKeys', this.cart)
                                    .then(response => {
                                        this.$store.dispatch('getCraftData')
                                            .then(() => {
                                                this.$store.dispatch('resetCart')
                                                    .then(() => {
                                                        this.loading = false
                                                        this.error = false
                                                        this.$root.modalStep = 'thankYou'
                                                    })
                                            })
                                    })
                            })
                            .catch(error => {
                                this.loading = false
                                this.error = error.response.data.error || error.response.statusText;
                            })
                    }, (response) => {
                        if (response.errors) {
                            response.errors.forEach(error => {
                                this.errors[error.param] = error.message
                            })
                        }
                        this.loading = false
                        this.$root.displayError("Couldn't save billing informations.")
                    })
                }, () => {
                    this.loading = false
                    this.$root.displayError("Couldn't save payment method.")
                })
            },

            onCountryChange(iso) {
                if (!this.countries[iso]) {
                    this.stateOptions = []
                    return
                }

                const country = this.countries[iso]

                if (!country.states) {
                    this.stateOptions = []
                    return
                }

                const states = country.states
                let options = []

                for (let iso in states) {
                    if (states.hasOwnProperty(iso)) {
                        options.push({
                            label: states[iso],
                            value: iso,
                        })
                    }
                }

                this.stateOptions = options
            },

            couponCodeChange(value) {
                clearTimeout(this.couponCodeTimeout)
                this.couponCodeSuccess = false
                this.couponCodeError = false

                this.couponCodeTimeout = setTimeout(function() {
                    this.couponCodeLoading = true

                    const data = {
                        couponCode: (value ? value : null),
                    }

                    this.$store.dispatch('saveCart', data)
                        .then(response => {
                            this.couponCodeSuccess = true
                            this.couponCodeError = false
                            this.staticCartTotal = this.cart.totalPrice
                            this.couponCodeLoading = false
                        })
                        .catch(response => {
                            this.couponCodeError = true
                            this.staticCartTotal = this.cart.totalPrice
                            this.couponCodeLoading = false
                        })
                }.bind(this), 500)
            }

        },

        mounted() {
            this.staticCartTotal = this.cart.totalPrice
            this.couponCode = this.cart.couponCode

            if (this.craftId && this.craftId.billingAddress) {
                if (this.craftId.card) {
                    this.paymentMode = 'existingCard'
                }

                if (this.craftId.billingAddress.country) {
                    this.onCountryChange(this.craftId.billingAddress.country)
                }

                this.$nextTick(() => {
                    this.billingInfo = JSON.parse(JSON.stringify(this.craftId.billingAddress))
                })
            }
        }

    }
</script>
