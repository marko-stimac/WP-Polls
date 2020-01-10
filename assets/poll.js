

var vm = new Vue({

	el: '#wppoll',

	data: {
		main_question: wppoll.data.main_question,
		items: wppoll.data.choices,
		canVote: true,
		isVoted: false,
		showPollData: false
	},

	mounted() {
		// Provjeri ako korisnik može glasati
		this.checkCanVote();
	},

	methods: {

		// On choice click event
		registerVote: function (item) {

			// If user can vote
			if (!this.isVoted && this.canVote) {
				// Increase vote for item in backend
				this.vote(item.index);
				// Increase vote for item in frontend
				this.items[item.index][1]++;
				// Increase total number of votes
				this.totalVotes++;
				// Calculate percentage of votes per each item
				this.calculate();

			}

			// If user can't vote
			if (!this.canVote) {
				// Calculate percentage of votes per each item
				this.calculate();
				// Show poll data
				this.showPollData = true;
			}


		},

		// Check session if user can vote
		checkCanVote: function () {

			var self = this;
			jQuery.ajax({
				data: {
					action: 'check_session',
					poll_id: wppoll.poll_id
				},
				type: 'POST',
				url: wppoll.url,
				success: function (data) {

					// If user has free session let him vote, otherwise disable it 
					self.canVote = data.can_vote === true ? true : false;

				},
				error: function (error) {
					console.log(error);
				}
			});

		},

		// Glasanje
		vote: function (index) {
			var self = this;
			jQuery.ajax({
				data: {
					action: 'vote',
					nonce: wppoll.nonce,
					votes_question: self.items[index][0],
					poll_id: wppoll.poll_id
				},
				type: 'POST',
				url: wppoll.url,
				success: function (xml, textStatus, xhr) {
					// If all went well 
					if (xhr.status == '200') {
						self.showPollData = true;
						self.isVoted = true;
					}
				},
				error: function (error) {
					console.log(error);
				}
			});

		},

		// Calculate percentage of votes for every choice 
		calculate: function () {
			totalVotes = this.totalVotes;
			this.items.forEach(function (item) {
				var rawPercentage = Math.round(item[1] * 100 / totalVotes);
				item.percentage = Math.round(rawPercentage / 10) * 10
			});
		}

	},

	computed: {

		// Calculate total votes 
		totalVotes: function () {
			var total = 0;
			this.items.forEach(function (item) {
				total += parseInt(item[1]);
			});
			return total;
		}

	}

});