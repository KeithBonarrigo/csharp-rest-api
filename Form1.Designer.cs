using System;

namespace WindowsFormsApp1
{
    partial class Form1
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.txtResponse = new System.Windows.Forms.TextBox();
            this.submitButton = new System.Windows.Forms.Button();
            this.comboBox1 = new System.Windows.Forms.ComboBox();
            this.comboBox2 = new System.Windows.Forms.ComboBox();
            this.textBox1 = new System.Windows.Forms.TextBox();
            this.label2 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.label4 = new System.Windows.Forms.Label();
            this.errorDisplay = new System.Windows.Forms.Label();
            this.interestUploadButton = new System.Windows.Forms.Button();
            this.button1 = new System.Windows.Forms.Button();
            this.clearWindowButton = new System.Windows.Forms.Button();
            this.conversionDataOutput = new System.Windows.Forms.TextBox();
            this.SuspendLayout();
            // 
            // txtResponse
            // 
            this.txtResponse.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.txtResponse.Location = new System.Drawing.Point(155, 182);
            this.txtResponse.Margin = new System.Windows.Forms.Padding(4);
            this.txtResponse.Multiline = true;
            this.txtResponse.Name = "txtResponse";
            this.txtResponse.Size = new System.Drawing.Size(757, 122);
            this.txtResponse.TabIndex = 1;
            this.txtResponse.TextChanged += new System.EventHandler(this.TxtResponse_TextChanged);
            // 
            // submitButton
            // 
            this.submitButton.Location = new System.Drawing.Point(768, 39);
            this.submitButton.Margin = new System.Windows.Forms.Padding(4);
            this.submitButton.Name = "submitButton";
            this.submitButton.Size = new System.Drawing.Size(100, 28);
            this.submitButton.TabIndex = 2;
            this.submitButton.Text = "Convert File";
            this.submitButton.UseVisualStyleBackColor = true;
            this.submitButton.Click += new System.EventHandler(this.submitButton_ClickAsync);
            // 
            // comboBox1
            // 
            this.comboBox1.FormattingEnabled = true;
            this.comboBox1.Items.AddRange(new object[] {
            "Y2532",
            "Y3210",
            "Y3373",
            "Y3401",
            "Y3432",
            "Y6051",
            "Y7501",
            "Y9083",
            "Y9155",
            "Y9234",
            "Y9275",
            "Y9650",
            "Y9658",
            "EBS15"});
            this.comboBox1.Location = new System.Drawing.Point(175, 39);
            this.comboBox1.Margin = new System.Windows.Forms.Padding(4);
            this.comboBox1.Name = "comboBox1";
            this.comboBox1.Size = new System.Drawing.Size(160, 24);
            this.comboBox1.TabIndex = 3;
            this.comboBox1.SelectedIndexChanged += new System.EventHandler(this.ComboBox1_SelectedIndexChanged);
            // 
            // comboBox2
            // 
            this.comboBox2.FormattingEnabled = true;
            this.comboBox2.Items.AddRange(new object[] {
            "New Account File",
            "Payment"});
            this.comboBox2.Location = new System.Drawing.Point(357, 39);
            this.comboBox2.Name = "comboBox2";
            this.comboBox2.Size = new System.Drawing.Size(121, 24);
            this.comboBox2.TabIndex = 4;
            this.comboBox2.SelectedIndexChanged += new System.EventHandler(this.ComboBox2_SelectedIndexChanged);
            // 
            // textBox1
            // 
            this.textBox1.Location = new System.Drawing.Point(155, 106);
            this.textBox1.Name = "textBox1";
            this.textBox1.Size = new System.Drawing.Size(757, 22);
            this.textBox1.TabIndex = 5;
            this.textBox1.Text = "File to Convert";
            this.textBox1.TextChanged += new System.EventHandler(this.TextBox1_TextChanged);
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Location = new System.Drawing.Point(172, 18);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(60, 17);
            this.label2.TabIndex = 7;
            this.label2.Text = "Client ID";
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.Location = new System.Drawing.Point(354, 18);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(99, 17);
            this.label3.TabIndex = 8;
            this.label3.Text = "Action to Take";
            // 
            // label4
            // 
            this.label4.AutoSize = true;
            this.label4.Location = new System.Drawing.Point(50, 161);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(0, 17);
            this.label4.TabIndex = 9;
            // 
            // errorDisplay
            // 
            this.errorDisplay.AutoSize = true;
            this.errorDisplay.Font = new System.Drawing.Font("Microsoft Sans Serif", 7.8F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.errorDisplay.ForeColor = System.Drawing.Color.Crimson;
            this.errorDisplay.Location = new System.Drawing.Point(175, 71);
            this.errorDisplay.Name = "errorDisplay";
            this.errorDisplay.Size = new System.Drawing.Size(14, 17);
            this.errorDisplay.TabIndex = 10;
            this.errorDisplay.Text = "-";
            // 
            // interestUploadButton
            // 
            this.interestUploadButton.Location = new System.Drawing.Point(511, 39);
            this.interestUploadButton.Name = "interestUploadButton";
            this.interestUploadButton.Size = new System.Drawing.Size(161, 28);
            this.interestUploadButton.TabIndex = 11;
            this.interestUploadButton.Text = "Interest File (optional)";
            this.interestUploadButton.UseVisualStyleBackColor = true;
            this.interestUploadButton.Click += new System.EventHandler(this.InterestUploadButton_Click);
            // 
            // button1
            // 
            this.button1.Location = new System.Drawing.Point(875, 39);
            this.button1.Name = "button1";
            this.button1.Size = new System.Drawing.Size(148, 27);
            this.button1.TabIndex = 12;
            this.button1.Text = "Preview Conversion";
            this.button1.UseVisualStyleBackColor = true;
            this.button1.Click += new System.EventHandler(this.Button1_Click);
            // 
            // clearWindowButton
            // 
            this.clearWindowButton.Location = new System.Drawing.Point(768, 75);
            this.clearWindowButton.Name = "clearWindowButton";
            this.clearWindowButton.Size = new System.Drawing.Size(260, 23);
            this.clearWindowButton.TabIndex = 13;
            this.clearWindowButton.Text = "Clear Window Text";
            this.clearWindowButton.UseVisualStyleBackColor = true;
            this.clearWindowButton.Click += new System.EventHandler(this.ClearWindowButton_Click);
            // 
            // conversionDataOutput
            // 
            this.conversionDataOutput.Location = new System.Drawing.Point(155, 313);
            this.conversionDataOutput.Multiline = true;
            this.conversionDataOutput.Name = "conversionDataOutput";
            this.conversionDataOutput.Size = new System.Drawing.Size(757, 219);
            this.conversionDataOutput.TabIndex = 14;
            this.conversionDataOutput.TextChanged += new System.EventHandler(this.TextBox2_TextChanged_1);
            // 
            // Form1
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(8F, 16F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(1067, 554);
            this.Controls.Add(this.conversionDataOutput);
            this.Controls.Add(this.clearWindowButton);
            this.Controls.Add(this.button1);
            this.Controls.Add(this.interestUploadButton);
            this.Controls.Add(this.errorDisplay);
            this.Controls.Add(this.label4);
            this.Controls.Add(this.label3);
            this.Controls.Add(this.label2);
            this.Controls.Add(this.textBox1);
            this.Controls.Add(this.comboBox2);
            this.Controls.Add(this.comboBox1);
            this.Controls.Add(this.submitButton);
            this.Controls.Add(this.txtResponse);
            this.Margin = new System.Windows.Forms.Padding(4);
            this.Name = "Form1";
            this.Text = "EFS Conversion Tool - Beta";
            this.Load += new System.EventHandler(this.Form1_Load);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        private void TxtResponse_TextChanged(object sender, EventArgs e)
        {
            //throw new NotImplementedException();
        }

        #endregion
        private System.Windows.Forms.TextBox txtResponse;
        private System.Windows.Forms.Button submitButton;
        public System.Windows.Forms.ComboBox comboBox1;
        private System.Windows.Forms.ComboBox comboBox2;
        private System.Windows.Forms.TextBox textBox1;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.Label label4;
        private System.Windows.Forms.Label errorDisplay;
        private System.Windows.Forms.Button interestUploadButton;
        private System.Windows.Forms.Button button1;
        private System.Windows.Forms.Button clearWindowButton;
        private System.Windows.Forms.TextBox conversionDataOutput;
    }
}

